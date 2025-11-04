# Shopware Tutorial - Olli's Learning Project

## Overview

This is a Shopware 6.7.3.1 development project created for learning Shopware plugin development. The project includes a complete HelloWorld plugin demonstration and comprehensive learning materials.

## 🎯 What's Inside

### Learning Materials
- **AI_DOCUMENTS/**: Comprehensive Shopware learning guides and tutorials
- **PLUGIN-HELLO-WORLD.md**: Complete step-by-step plugin development tutorial
- **Example Plugin**: "Olli's HelloWorld Plugin" - a fully functional demonstration plugin

### Plugin Features
- Custom `/hello-world` route in storefront
- Product listing with repository usage
- **Configurable text** via admin panel (Settings → Extensions)
- Proper service injection and dependency management
- Clean plugin lifecycle management (activate/deactivate)

## 🚀 Setup Instructions

This project uses the **Symfony CLI approach** for Shopware development. Follow these steps to get it running locally.

### Prerequisites

Before starting, ensure you have:
- **PHP 8.1+** with required extensions
- **Node.js 18+** and npm (for asset building)
- **MariaDB/MySQL** database server
- **Symfony CLI** installed
- **Docker** (for database container)

### Installation Guide

#### 1. Install Symfony CLI

Follow the official Symfony CLI installation: https://symfony.com/download

#### 2. Clone and Setup Project

```bash
# Clone the repository
git clone <your-repo-url>
cd shopware-tutorial-olli

# Install PHP dependencies
composer install
```

#### 3. Database Setup

This project uses a **Docker MariaDB container** for the database:

```bash
# Start MariaDB container (runs on port 60233)
docker run -d \
  --name shopware-db \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=shopware \
  -e MYSQL_USER=shopware \
  -e MYSQL_PASSWORD=shopware \
  -p 60233:3306 \
  mariadb:11.8
```

#### 4. Environment Configuration

The project includes a pre-configured `.env` file with:
- **Database**: MariaDB on `127.0.0.1:60233`
- **App URL**: `http://127.0.0.1:8000`
- **Development mode** enabled

If you need to adjust settings, check `.env` file.

#### 5. Database Migration

```bash
# Run database migrations to set up Shopware
bin/console system:install --create-database --basic-setup
```

#### 6. Build Assets

```bash
# Build storefront assets
./bin/build-storefront.sh

# Build administration assets  
./bin/build-administration.sh

# Or build both storefront and JS assets
./bin/build-js.sh

# For development with file watching:
./bin/watch-storefront.sh      # Watch storefront changes
./bin/watch-administration.sh  # Watch admin changes
```

#### 7. Start Development Server

```bash
# Start Symfony development server
symfony server:start
```

Your Shopware shop will be available at: **http://127.0.0.1:8000**

### 📚 Official Documentation References

This setup follows the official Shopware installation guides:

- **Main Installation Guide**: https://developer.shopware.com/docs/guides/installation/
- **Symfony CLI Setup**: https://developer.shopware.com/docs/guides/installation/setups/symfony-cli.html

## 🔧 Development Workflow

### Plugin Development

The project includes "Olli's HelloWorld Plugin" as a learning example:

```bash
# Plugin is located at:
custom/plugins/MyFirstPlugin/

# Manage plugin lifecycle:
bin/console plugin:refresh           # Detect new plugins
bin/console plugin:install MyFirstPlugin
bin/console plugin:activate MyFirstPlugin
bin/console plugin:update MyFirstPlugin

# After changes, clear cache:
bin/console cache:clear
```

### Accessing the HelloWorld Plugin

1. **Storefront**: Visit http://127.0.0.1:8000/hello-world
2. **Configuration**: Admin Panel → Settings → Extensions → MyFirstPlugin
3. **Admin Panel**: http://127.0.0.1:8000/admin (use credentials from installation)

## 🎓 Learning Resources

### Included Tutorials

1. **AI_DOCUMENTS/01_SHOPWARE_GETTING_STARTED.md**: Shopware basics
2. **AI_DOCUMENTS/TUTORIALS/PLUGIN-HELLO-WORLD.md**: Complete plugin tutorial
3. **Plugin Code**: Real working example in `custom/plugins/MyFirstPlugin/`

### Plugin Tutorial Features

The HelloWorld tutorial covers:
- ✅ Plugin structure and naming conventions
- ✅ Controller creation and routing  
- ✅ Service injection and repository usage
- ✅ Template creation and variables
- ✅ System configuration integration
- ✅ Plugin lifecycle management
- ✅ Troubleshooting common issues

## 🐛 Troubleshooting

### Common Issues

**Port conflicts**: If port 8000 or 60233 are in use:
```bash
# Check what's using the port
lsof -i :8000
lsof -i :60233

# Stop conflicting services or use different ports
```

**Database connection issues**:
```bash
# Verify MariaDB container is running
docker ps | grep shopware-db

# Check database connectivity
mysql -h 127.0.0.1 -P 60233 -u shopware -pshopware -D shopware -e "SELECT 1;"
```

**Plugin not working**:
```bash
# Clear all caches
bin/console cache:clear
rm -rf var/cache/*

# Reinstall plugin if needed
bin/console plugin:uninstall MyFirstPlugin
bin/console plugin:install --activate MyFirstPlugin
```

**Assets not loading**:
```bash
# Rebuild storefront assets
./bin/build-storefront.sh

# Rebuild administration assets
./bin/build-administration.sh

# Or rebuild all JS assets
./bin/build-js.sh
```

## 📁 Project Structure

```
shopware-tutorial-olli/
├── README.md                          # This file
├── AI_DOCUMENTS/                      # Learning materials
│   ├── 01_SHOPWARE_GETTING_STARTED.md
│   └── TUTORIALS/
│       └── PLUGIN-HELLO-WORLD.md     # Complete plugin tutorial
├── custom/plugins/
│   └── MyFirstPlugin/                 # Example HelloWorld plugin
├── .env                               # Environment configuration
├── composer.json                      # PHP dependencies
├── bin/                               # Build scripts and console
│   ├── build-storefront.sh           # Build storefront assets
│   ├── build-administration.sh       # Build admin assets
│   └── console                       # Shopware CLI tool
```

## 🎉 Next Steps

Once you have the project running:

1. **Explore the HelloWorld plugin** at http://127.0.0.1:8000/hello-world
2. **Try configuring the plugin** via admin panel
3. **Follow the plugin tutorial** in `AI_DOCUMENTS/TUTORIALS/PLUGIN-HELLO-WORLD.md`
4. **Create your own plugin** using the tutorial as a guide

## 🤝 Contributing

This is a learning project, but contributions to improve the tutorials or fix issues are welcome!

## 📄 License

This project is for educational purposes. Shopware is licensed under the MIT License.

---

**Happy coding! 🚀**

*For detailed plugin development guidance, see the comprehensive tutorial in `AI_DOCUMENTS/TUTORIALS/PLUGIN-HELLO-WORLD.md`*