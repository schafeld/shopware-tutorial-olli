# Shopware 6 Administration Customization Guide

## Administration Architecture Overview 🎛️

The Shopware 6 Administration is a Vue.js-based single-page application (SPA) that provides:
- **Vue.js 2/3**: Component-based frontend framework
- **Vuex**: State management
- **Vue Router**: Client-side routing  
- **Webpack**: Asset bundling
- **SCSS**: Styling with custom design system
- **REST API**: Communication with Shopware backend

## Directory Structure

### 1. Administration Structure
```
custom/plugins/MyPlugin/src/Resources/app/administration/src/
├── main.js                          # Entry point
├── module/                          # Custom modules
│   └── my-custom-module/
│       ├── index.js                 # Module definition
│       ├── page/                    # Pages/views
│       ├── component/               # Components
│       └── snippet/                 # Translations
├── component/                       # Global components
├── service/                         # API services
├── decorator/                       # Service decorators
├── init/                           # Initialization
└── snippet/                        # Global translations
    ├── en-GB.json
    └── de-DE.json
```

### 2. Core Administration Paths
```
vendor/shopware/administration/Resources/app/administration/src/
├── app/                            # Core application
├── core/                           # Core services & helpers
├── module/                         # Core modules (product, category, etc.)
└── component/                      # Core components
```

## Creating Custom Modules

### 1. Module Registration
```javascript
// custom/plugins/MyPlugin/src/Resources/app/administration/src/main.js

import './module/my-product-manager';

// The module will auto-register itself when imported
```

### 2. Module Definition
```javascript
// custom/plugins/MyPlugin/src/Resources/app/administration/src/module/my-product-manager/index.js

import './page/my-product-list';
import './page/my-product-detail';
import './component/my-product-card';

const { Module } = Shopware;

Module.register('my-product-manager', {
    type: 'plugin',
    name: 'my-product-manager',
    title: 'my-plugin.module.title',
    description: 'my-plugin.module.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#ff68b4',
    icon: 'default-shopping-paper-bag-product',
    
    routes: {
        list: {
            component: 'my-product-list',
            path: 'list',
            meta: {
                privilege: 'product.viewer'
            }
        },
        detail: {
            component: 'my-product-detail',
            path: 'detail/:id?',
            meta: {
                privilege: 'product.viewer'
            },
            props: {
                default: (route) => ({ productId: route.params.id })
            }
        }
    },

    navigation: [{
        id: 'my-product-manager',
        label: 'my-plugin.module.title',
        color: '#ff68b4',
        path: 'my.product.manager.list',
        icon: 'default-shopping-paper-bag-product',
        parent: 'sw-catalogue',
        privilege: 'product.viewer',
        position: 100
    }]
});
```

### 3. List Page Component
```javascript
// custom/plugins/MyPlugin/src/Resources/app/administration/src/module/my-product-manager/page/my-product-list/index.js

import template from './my-product-list.html.twig';
import './my-product-list.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('my-product-list', {
    template,

    inject: ['repositoryFactory', 'acl'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],

    data() {
        return {
            products: [],
            isLoading: true,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            searchTerm: '',
            showDeleteModal: false,
            productToDelete: null
        };
    },

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productColumns() {
            return [
                {
                    property: 'name',
                    label: this.$t('sw-product.list.columnName'),
                    routerLink: 'my.product.manager.detail',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'productNumber',
                    label: this.$t('sw-product.list.columnProductNumber'),
                    allowResize: true
                },
                {
                    property: 'stock',
                    label: this.$t('sw-product.list.columnStock'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'price',
                    label: this.$t('sw-product.list.columnPrice'),
                    allowResize: true
                },
                {
                    property: 'active',
                    label: this.$t('sw-product.list.columnActive'),
                    allowResize: true,
                    align: 'center'
                }
            ];
        },

        productCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria.setTerm(this.searchTerm);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));
            criteria.addAssociation('media');
            criteria.addAssociation('categories');

            return criteria;
        }
    },

    methods: {
        async getList() {
            this.isLoading = true;

            try {
                const result = await this.productRepository.search(this.productCriteria);
                
                this.products = result;
                this.total = result.total;
                this.page = result.page;
                this.limit = result.limit;
            } catch (error) {
                this.createNotificationError({
                    message: this.$t('my-plugin.notification.loadError')
                });
            } finally {
                this.isLoading = false;
            }
        },

        onSearch(searchTerm) {
            this.searchTerm = searchTerm;
            this.page = 1;
            this.getList();
        },

        onSortColumn(column) {
            if (this.sortBy === column.dataIndex) {
                this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = column.dataIndex;
                this.sortDirection = 'DESC';
            }

            this.getList();
        },

        onChangeLanguage() {
            this.getList();
        },

        async onDeleteProduct(product) {
            this.productToDelete = product;
            this.showDeleteModal = true;
        },

        async onConfirmDelete() {
            if (!this.productToDelete) {
                return;
            }

            try {
                await this.productRepository.delete(this.productToDelete.id);
                
                this.createNotificationSuccess({
                    message: this.$t('my-plugin.notification.deleteSuccess')
                });
                
                this.getList();
            } catch (error) {
                this.createNotificationError({
                    message: this.$t('my-plugin.notification.deleteError')
                });
            } finally {
                this.showDeleteModal = false;
                this.productToDelete = null;
            }
        }
    },

    created() {
        this.getList();
    }
});
```

### 4. List Page Template
```twig
{# custom/plugins/MyPlugin/src/Resources/app/administration/src/module/my-product-manager/page/my-product-list/my-product-list.html.twig #}

<sw-page class="my-product-list">
    <template #search-bar>
        <sw-search-bar
            :placeholder="$t('my-plugin.list.searchPlaceholder')"
            :searchType="$t('my-plugin.general.mainMenuItemGeneral')"
            @search="onSearch">
        </sw-search-bar>
    </template>

    <template #smart-bar-header>
        <h2>{{ $t('my-plugin.list.title') }}</h2>
    </template>

    <template #smart-bar-actions>
        <sw-button
            v-if="acl.can('product.creator')"
            :routerLink="{ name: 'my.product.manager.detail' }"
            variant="primary">
            {{ $t('my-plugin.list.addProduct') }}
        </sw-button>
    </template>

    <template #content>
        <sw-entity-listing
            v-if="products"
            :items="products"
            :columns="productColumns"
            :repository="productRepository"
            :showSelection="acl.can('product.deleter')"
            :isLoading="isLoading"
            :allowEdit="acl.can('product.editor')"
            :allowDelete="acl.can('product.deleter')"
            detailRoute="my.product.manager.detail"
            @page-change="onPageChange"
            @sort-column="onSortColumn"
            @delete-item="onDeleteProduct">

            <template #column-price="{ item }">
                <span v-if="item.calculatedPrice">
                    {{ item.calculatedPrice.unitPrice | currency }}
                </span>
                <span v-else>-</span>
            </template>

            <template #column-active="{ item }">
                <sw-icon
                    :name="item.active ? 'small-default-checkmark-line-medium' : 'small-default-x-line-medium'"
                    :color="item.active ? '#37d046' : '#de294c'">
                </sw-icon>
            </template>

            <template #actions="{ item }">
                <sw-context-menu-item
                    class="my-product-duplicate"
                    :disabled="!acl.can('product.creator')"
                    @click="onDuplicateProduct(item)">
                    {{ $t('my-plugin.list.duplicate') }}
                </sw-context-menu-item>
            </template>
        </sw-entity-listing>

        <sw-empty-state
            v-else-if="!isLoading"
            :title="$t('my-plugin.list.emptyTitle')"
            :subline="$t('my-plugin.list.emptySubline')"
            icon="default-shopping-paper-bag-product">
        </sw-empty-state>
    </template>

    <!-- Delete Modal -->
    <sw-modal
        v-if="showDeleteModal"
        :title="$t('my-plugin.modal.deleteTitle')"
        @modal-close="showDeleteModal = false">

        <p>{{ $t('my-plugin.modal.deleteText') }}</p>
        <strong>{{ productToDelete.name }}</strong>

        <template #modal-footer>
            <sw-button
                size="small"
                @click="showDeleteModal = false">
                {{ $t('my-plugin.modal.cancel') }}
            </sw-button>

            <sw-button
                size="small"
                variant="danger"
                @click="onConfirmDelete">
                {{ $t('my-plugin.modal.delete') }}
            </sw-button>
        </template>
    </sw-modal>
</sw-page>
```

## Custom Components

### 1. Component Registration
```javascript
// custom/plugins/MyPlugin/src/Resources/app/administration/src/component/my-product-card/index.js

import template from './my-product-card.html.twig';
import './my-product-card.scss';

const { Component } = Shopware;

Component.register('my-product-card', {
    template,

    props: {
        product: {
            type: Object,
            required: true
        },
        showActions: {
            type: Boolean,
            default: true
        }
    },

    computed: {
        productImageUrl() {
            if (this.product.media && this.product.media.length > 0) {
                return this.product.media[0].url;
            }
            return null;
        },

        productPrice() {
            if (this.product.calculatedPrice) {
                return this.product.calculatedPrice.unitPrice;
            }
            return 0;
        },

        stockStatus() {
            if (this.product.stock <= 0) {
                return 'out-of-stock';
            } else if (this.product.stock <= 10) {
                return 'low-stock';
            }
            return 'in-stock';
        }
    },

    methods: {
        onEdit() {
            this.$emit('edit', this.product);
        },

        onDelete() {
            this.$emit('delete', this.product);
        },

        onDuplicate() {
            this.$emit('duplicate', this.product);
        }
    }
});
```

### 2. Component Template
```twig
{# custom/plugins/MyPlugin/src/Resources/app/administration/src/component/my-product-card/my-product-card.html.twig #}

<div class="my-product-card">
    <div class="my-product-card__image">
        <img
            v-if="productImageUrl"
            :src="productImageUrl"
            :alt="product.name"
            class="my-product-card__image-element">
        
        <div
            v-else
            class="my-product-card__image-placeholder">
            <sw-icon name="default-shopping-paper-bag-product" size="48"></sw-icon>
        </div>

        <div class="my-product-card__badges">
            <span
                v-if="!product.active"
                class="my-product-card__badge my-product-card__badge--inactive">
                {{ $t('my-plugin.card.inactive') }}
            </span>

            <span
                :class="[
                    'my-product-card__badge',
                    'my-product-card__badge--stock',
                    `my-product-card__badge--${stockStatus}`
                ]">
                {{ product.stock }}
            </span>
        </div>
    </div>

    <div class="my-product-card__content">
        <h3 class="my-product-card__title">{{ product.name }}</h3>
        
        <p class="my-product-card__number">
            {{ $t('my-plugin.card.productNumber') }}: {{ product.productNumber }}
        </p>

        <div class="my-product-card__price">
            {{ productPrice | currency }}
        </div>

        <div
            v-if="product.categories && product.categories.length"
            class="my-product-card__categories">
            <sw-label
                v-for="category in product.categories"
                :key="category.id"
                size="small"
                variant="info">
                {{ category.name }}
            </sw-label>
        </div>
    </div>

    <div
        v-if="showActions"
        class="my-product-card__actions">
        <sw-button
            size="small"
            @click="onEdit">
            {{ $t('my-plugin.card.edit') }}
        </sw-button>

        <sw-context-button>
            <template #button>
                <sw-button
                    size="small"
                    square>
                    <sw-icon name="small-more"></sw-icon>
                </sw-button>
            </template>

            <template #content>
                <sw-context-menu-item @click="onDuplicate">
                    {{ $t('my-plugin.card.duplicate') }}
                </sw-context-menu-item>

                <sw-context-menu-item
                    variant="danger"
                    @click="onDelete">
                    {{ $t('my-plugin.card.delete') }}
                </sw-context-menu-item>
            </template>
        </sw-context-button>
    </div>
</div>
```

### 3. Component Styling
```scss
// custom/plugins/MyPlugin/src/Resources/app/administration/src/component/my-product-card/my-product-card.scss

.my-product-card {
    background: #fff;
    border: 1px solid #d1d9e0;
    border-radius: 4px;
    overflow: hidden;
    transition: box-shadow 0.2s ease;

    &:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    &__image {
        position: relative;
        height: 200px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__image-element {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }

    &__image-placeholder {
        color: #52667a;
    }

    &__badges {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__badge {
        background: rgba(0, 0, 0, 0.7);
        color: #fff;
        padding: 2px 6px;
        border-radius: 2px;
        font-size: 12px;
        font-weight: 500;

        &--inactive {
            background: #de294c;
        }

        &--stock {
            &.my-product-card__badge--out-of-stock {
                background: #de294c;
            }

            &.my-product-card__badge--low-stock {
                background: #ffbd5d;
            }

            &.my-product-card__badge--in-stock {
                background: #37d046;
            }
        }
    }

    &__content {
        padding: 16px;
    }

    &__title {
        margin: 0 0 8px;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.3;
        color: #2a3345;
    }

    &__number {
        margin: 0 0 8px;
        font-size: 12px;
        color: #758ca3;
    }

    &__price {
        margin-bottom: 12px;
        font-size: 18px;
        font-weight: 600;
        color: #189eff;
    }

    &__categories {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    &__actions {
        padding: 12px 16px;
        border-top: 1px solid #d1d9e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
}
```

## API Services

### 1. Custom API Service
```javascript
// custom/plugins/MyPlugin/src/Resources/app/administration/src/service/my-api.service.js

const { Application } = Shopware;

class MyApiService {
    constructor(httpClient, loginService) {
        this.httpClient = httpClient;
        this.loginService = loginService;
        this.name = 'myApiService';
    }

    async getCustomData(params = {}) {
        const headers = await this.getHeaders();
        
        return this.httpClient.get('/api/_action/my-plugin/custom-data', {
            params,
            headers
        });
    }

    async updateCustomData(id, data) {
        const headers = await this.getHeaders();
        
        return this.httpClient.patch(`/api/_action/my-plugin/custom-data/${id}`, data, {
            headers
        });
    }

    async deleteCustomData(id) {
        const headers = await this.getHeaders();
        
        return this.httpClient.delete(`/api/_action/my-plugin/custom-data/${id}`, {
            headers
        });
    }

    async getHeaders() {
        return {
            Accept: 'application/vnd.api+json',
            Authorization: `Bearer ${this.loginService.getToken()}`,
            'Content-Type': 'application/json'
        };
    }
}

// Register the service
Application.addServiceProvider('myApiService', (container) => {
    const initContainer = Application.getContainer('init');
    
    return new MyApiService(
        initContainer.httpClient,
        initContainer.loginService
    );
});
```

### 2. Using API Service in Components
```javascript
// In your component
export default {
    inject: ['myApiService'],

    data() {
        return {
            customData: [],
            isLoading: false
        };
    },

    methods: {
        async loadCustomData() {
            this.isLoading = true;
            
            try {
                const response = await this.myApiService.getCustomData();
                this.customData = response.data;
            } catch (error) {
                this.createNotificationError({
                    message: 'Failed to load custom data'
                });
            } finally {
                this.isLoading = false;
            }
        }
    },

    created() {
        this.loadCustomData();
    }
};
```

## Translations & Snippets

### 1. Snippet Files
```json
// custom/plugins/MyPlugin/src/Resources/app/administration/src/snippet/en-GB.json
{
    "my-plugin": {
        "general": {
            "mainMenuItemGeneral": "My Plugin",
            "descriptionTextModule": "Manage custom products"
        },
        "module": {
            "title": "Product Manager",
            "description": "Advanced product management"
        },
        "list": {
            "title": "Products",
            "searchPlaceholder": "Search products...",
            "addProduct": "Add Product",
            "emptyTitle": "No products found",
            "emptySubline": "Start by adding your first product",
            "duplicate": "Duplicate"
        },
        "card": {
            "productNumber": "Product Number",
            "inactive": "Inactive",
            "edit": "Edit",
            "duplicate": "Duplicate", 
            "delete": "Delete"
        },
        "modal": {
            "deleteTitle": "Delete Product",
            "deleteText": "Are you sure you want to delete this product?",
            "cancel": "Cancel",
            "delete": "Delete"
        },
        "notification": {
            "loadError": "Failed to load products",
            "deleteSuccess": "Product deleted successfully",
            "deleteError": "Failed to delete product"
        }
    }
}
```

## Build Configuration

### 1. Webpack Extension
```javascript
// custom/plugins/MyPlugin/src/Resources/app/administration/build/webpack.config.js

const { join, resolve } = require('path');

module.exports = () => {
    return {
        resolve: {
            alias: {
                '@MyPlugin': resolve(
                    join(__dirname, '..', 'src')
                )
            }
        }
    };
};
```

### 2. Build Commands
```bash
# Build administration
./bin/build-administration.sh

# Watch for changes
./bin/watch-administration.sh

# Build specific plugin
./bin/console bundle:dump
npm --prefix vendor/shopware/administration/Resources/app/administration/ run build
```

## Testing Administration Extensions

### 1. Jest Unit Tests
```javascript
// custom/plugins/MyPlugin/tests/unit/component/my-product-card.spec.js

import { shallowMount, createLocalVue } from '@vue/test-utils';
import MyProductCard from '@MyPlugin/component/my-product-card';

const localVue = createLocalVue();

describe('MyProductCard', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(MyProductCard, {
            localVue,
            propsData: {
                product: {
                    id: '123',
                    name: 'Test Product',
                    productNumber: 'TEST-001',
                    stock: 10,
                    active: true
                }
            }
        });
    });

    it('should render product name correctly', () => {
        expect(wrapper.find('.my-product-card__title').text()).toBe('Test Product');
    });

    it('should emit edit event when edit button is clicked', () => {
        wrapper.find('.my-product-card__actions button').trigger('click');
        
        expect(wrapper.emitted().edit).toBeTruthy();
        expect(wrapper.emitted().edit[0]).toEqual([wrapper.props().product]);
    });
});
```

## Advanced Patterns

### 1. State Management with Services
```javascript
// State service for complex data management
class ProductStateService {
    constructor() {
        this.state = {
            products: [],
            filters: {},
            sorting: {},
            loading: false
        };
        this.subscribers = [];
    }

    subscribe(callback) {
        this.subscribers.push(callback);
    }

    notify() {
        this.subscribers.forEach(callback => callback(this.state));
    }

    setProducts(products) {
        this.state.products = products;
        this.notify();
    }

    setFilters(filters) {
        this.state.filters = filters;
        this.notify();
    }
}
```

### 2. Custom Decorators
```javascript
// Service decorator to extend existing services
const { Application } = Shopware;

Application.addServiceProviderDecorator('productService', (productService) => {
    const originalGetList = productService.getList.bind(productService);
    
    productService.getList = function(criteria, context) {
        // Add custom logic before original call
        console.log('Getting product list with criteria:', criteria);
        
        return originalGetList(criteria, context).then(result => {
            // Add custom logic after original call
            console.log('Retrieved products:', result);
            return result;
        });
    };
    
    return productService;
});
```

This guide provides a comprehensive foundation for customizing the Shopware 6 Administration panel. Remember to follow Vue.js best practices and test your components thoroughly.