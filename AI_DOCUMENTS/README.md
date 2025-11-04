# AI_DOCUMENTS - Your Shopware Learning Hub 📚

Welcome to your comprehensive Shopware 6 learning resource collection! These AI-generated documents are designed to support your onboarding journey at your new Shopware company.

## 📖 Document Overview

### Getting Started
- **[01_SHOPWARE_GETTING_STARTED.md](./01_SHOPWARE_GETTING_STARTED.md)**  
  Essential first steps, project overview, and development workflow introduction

- **[08_NEWCOMER_TIPS.md](./08_NEWCOMER_TIPS.md)**  
  Common gotchas, pitfalls to avoid, and productivity hacks for new developers

### Core Development
- **[02_PLUGIN_DEVELOPMENT.md](./02_PLUGIN_DEVELOPMENT.md)**  
  Complete guide to creating and structuring Shopware plugins

- **[04_DATA_STRUCTURE_GUIDE.md](./04_DATA_STRUCTURE_GUIDE.md)**  
  Understanding entities, repositories, and the Data Abstraction Layer (DAL)

- **[05_BEST_PRACTICES.md](./05_BEST_PRACTICES.md)**  
  Code quality standards, performance optimization, and security practices

### Frontend Development
- **[06_STOREFRONT_CUSTOMIZATION.md](./06_STOREFRONT_CUSTOMIZATION.md)**  
  Template system, SCSS styling, JavaScript plugins, and theme development

- **[07_ADMINISTRATION_GUIDE.md](./07_ADMINISTRATION_GUIDE.md)**  
  Vue.js-based admin panel customization and module creation

### Reference Materials
- **[03_COMMAND_CHEATSHEET.md](./03_COMMAND_CHEATSHEET.md)**  
  Complete command reference for daily development tasks

- **[09_QUICK_REFERENCE.md](./09_QUICK_REFERENCE.md)**  
  Code snippets, patterns, and quick lookup reference

## 🎯 How to Use These Documents

### For New Developers
1. Start with **Getting Started** and **Newcomer Tips**
2. Read **Plugin Development** and **Data Structure Guide** 
3. Focus on either **Storefront** or **Administration** based on your role
4. Keep **Quick Reference** and **Command Cheatsheet** handy

### For Specific Tasks
- **Building plugins** → Plugin Development + Best Practices
- **Frontend work** → Storefront Customization + Quick Reference  
- **Admin customization** → Administration Guide + Best Practices
- **Troubleshooting** → Newcomer Tips + Command Cheatsheet

## 🚀 Quick Start Workflow

```bash
# 1. Set up development environment
APP_ENV=dev  # in your .env file
bin/console cache:clear

# 2. Start watching for changes
./bin/watch-storefront.sh      # Terminal 1 (if doing frontend)
./bin/watch-administration.sh  # Terminal 2 (if doing admin)

# 3. Create your first plugin
bin/console plugin:create MyFirstPlugin
cd custom/plugins/MyFirstPlugin

# 4. Install and activate
bin/console plugin:refresh
bin/console plugin:install --activate MyFirstPlugin
```

## 📋 Learning Path Recommendations

### Week 1: Foundation
- [ ] Complete Getting Started guide
- [ ] Set up your development environment  
- [ ] Read through Newcomer Tips
- [ ] Create your first simple plugin
- [ ] Familiarize yourself with commands

### Week 2: Core Concepts
- [ ] Study Data Structure Guide thoroughly
- [ ] Practice repository patterns and criteria building
- [ ] Implement event subscribers
- [ ] Learn service container usage

### Week 3: Frontend Focus
- [ ] Template inheritance and customization
- [ ] SCSS workflows and theming
- [ ] JavaScript plugin development
- [ ] Responsive design patterns

### Week 4: Advanced Topics
- [ ] Administration module creation (if applicable)
- [ ] API development
- [ ] Performance optimization
- [ ] Testing strategies

## 🔧 Your Project Context

Based on your Shopware 6.7.3.1 project:
- **Environment**: Development setup recommended (`APP_ENV=dev`)
- **Database**: MySQL configured at localhost
- **URL**: http://127.0.0.1:8000
- **Plugin Directory**: `custom/plugins/`
- **Custom Code**: Place in `src/` directory

## 💡 Pro Tips for Success

1. **Always clear cache** after configuration changes
2. **Use watch modes** during active development  
3. **Follow PSR standards** for clean, maintainable code
4. **Test in both environments** (dev and prod)
5. **Document your changes** as you learn
6. **Ask questions** - the Shopware community is helpful!

## 🆘 When You're Stuck

1. **Check the logs**: `var/log/dev.log` or `var/log/prod.log`
2. **Clear everything**: Cache, compiled assets, etc.
3. **Verify plugin structure**: Use the templates in these docs
4. **Search the official docs**: [developer.shopware.com](https://developer.shopware.com)
5. **Ask your team** - they've likely faced similar challenges!

## 🔄 Keeping Updated

These documents are tailored to Shopware 6.7.3.1. As you grow and the platform evolves:
- Update the documents with your learnings
- Add project-specific notes and discoveries  
- Share insights with your team
- Contribute back to the community

## 📞 Additional Resources

- [Official Shopware Developer Docs](https://developer.shopware.com/)
- [Shopware GitHub Repository](https://github.com/shopware/platform)
- [Community Forum](https://forum.shopware.com/)
- [Shopware Academy](https://academy.shopware.com/)

---

**Remember**: Every expert was once a beginner. Take your time to understand the concepts, experiment freely in your development environment, and don't hesitate to break things – that's how you learn!

Good luck with your Shopware journey! 🚀