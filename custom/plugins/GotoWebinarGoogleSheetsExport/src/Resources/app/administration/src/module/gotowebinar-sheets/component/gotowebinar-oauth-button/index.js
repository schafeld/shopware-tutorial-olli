import template from './gotowebinar-oauth-button.html.twig';

const { Component, Mixin } = Shopware;

/**
 * OAuth authorization button component
 */
Component.register('gotowebinar-oauth-button', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        isConfigured: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            isConnecting: false,
            authWindow: null,
            checkInterval: null
        };
    },

    computed: {
        buttonLabel() {
            if (this.isConnecting) {
                return this.$tc('gotowebinar-sheets.oauth.buttonLabelConnecting');
            }
            if (this.isConfigured) {
                return this.$tc('gotowebinar-sheets.oauth.buttonLabelConnected');
            }
            return this.$tc('gotowebinar-sheets.oauth.buttonLabel');
        },

        buttonVariant() {
            return this.isConfigured ? 'success' : 'primary';
        },

        iconName() {
            return this.isConfigured ? 'default-action-check' : 'default-action-share';
        }
    },

    beforeDestroy() {
        this.cleanup();
    },

    methods: {
        onButtonClick() {
            if (this.isConfigured) {
                // Reconnect
                this.startOAuthFlow();
            } else {
                this.startOAuthFlow();
            }
        },

        startOAuthFlow() {
            this.isConnecting = true;

            const redirectUri = `${window.location.origin}/admin`;

            // Get authorization URL from API
            this.$http.post('/_action/gotowebinar-sheets/oauth/authorize', {
                redirectUri: redirectUri
            })
                .then((response) => {
                    if (response.data.success && response.data.authUrl) {
                        this.openAuthWindow(response.data.authUrl);
                    } else {
                        throw new Error('Failed to get authorization URL');
                    }
                })
                .catch((error) => {
                    this.createNotificationError({
                        message: this.$tc('gotowebinar-sheets.oauth.errorMessage', 0, {
                            message: error.message
                        })
                    });
                    this.isConnecting = false;
                });
        },

        openAuthWindow(authUrl) {
            const width = 600;
            const height = 700;
            const left = window.screen.width / 2 - width / 2;
            const top = window.screen.height / 2 - height / 2;

            this.authWindow = window.open(
                authUrl,
                'GoogleOAuth',
                `width=${width},height=${height},left=${left},top=${top}`
            );

            if (!this.authWindow) {
                this.createNotificationWarning({
                    message: this.$tc('gotowebinar-sheets.oauth.popupBlocked')
                });
                this.isConnecting = false;
                return;
            }

            // Monitor the popup window
            this.checkInterval = setInterval(() => {
                this.checkAuthWindow();
            }, 500);
        },

        checkAuthWindow() {
            if (!this.authWindow || this.authWindow.closed) {
                this.cleanup();
                this.isConnecting = false;
                return;
            }

            try {
                const currentUrl = this.authWindow.location.href;
                
                // Check if redirected back to admin
                if (currentUrl.includes('/admin')) {
                    const urlParams = new URLSearchParams(this.authWindow.location.search);
                    const code = urlParams.get('code');

                    if (code) {
                        this.authWindow.close();
                        this.handleAuthCode(code);
                    }
                }
            } catch (e) {
                // Cross-origin errors are expected during OAuth flow
            }
        },

        handleAuthCode(code) {
            const redirectUri = `${window.location.origin}/admin`;

            this.$http.post('/_action/gotowebinar-sheets/oauth/callback', {
                code: code,
                redirectUri: redirectUri
            })
                .then((response) => {
                    if (response.data.success) {
                        this.createNotificationSuccess({
                            message: this.$tc('gotowebinar-sheets.oauth.successMessage')
                        });
                        this.$emit('success');
                    } else {
                        throw new Error(response.data.message || 'OAuth callback failed');
                    }
                })
                .catch((error) => {
                    this.createNotificationError({
                        message: this.$tc('gotowebinar-sheets.oauth.errorMessage', 0, {
                            message: error.message
                        })
                    });
                })
                .finally(() => {
                    this.cleanup();
                    this.isConnecting = false;
                });
        },

        cleanup() {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
                this.checkInterval = null;
            }
            if (this.authWindow && !this.authWindow.closed) {
                this.authWindow.close();
            }
            this.authWindow = null;
        }
    }
});
