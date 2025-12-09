# Shopware 6 Frontend-Focused Development - Onboarding Course

## 📚 Course Overview

This comprehensive course takes you from Shopware basics to building production-ready **storefront features**. **Specially designed for frontend developers** transitioning to Shopware, with emphasis on **Twig templates, JavaScript, and SCSS** while covering essential backend concepts.

**Total Duration:** 2-3 weeks (80-110 hours with breaks)  
**Skill Level:** Beginner to Intermediate  
**Focus:** 60% Frontend (Twig/JS/CSS), 40% Backend (PHP/API)  
**Prerequisites:** HTML/CSS/JavaScript, basic PHP knowledge, working Shopware 6 installation

> **💡 Important Note for Frontend Developers:** This course emphasizes storefront development with **lots of practical Twig and JavaScript work**. You'll learn backend concepts through building visible frontend features!

---

## 🎯 Course Structure

### [Day 1: Plugin Basics and Structure](./DAY_1_PLUGIN_BASICS.md)
**Duration:** 1-2 days (8-12 hours with breaks)  
**Difficulty:** ⭐⭐ Beginner-friendly

Learn the fundamentals of Shopware plugin development:
- Plugin architecture and file structure
- Creating your first plugin
- Services and dependency injection
- Console commands
- Plugin configuration system

**Key Outcomes:**
- Understand Shopware plugin structure
- Create and activate a working plugin
- Implement services with DI
- Add configuration options

**✅ Complete Solutions Available:** [DAY_1_SOLUTIONS.md](./SOLUTIONS/DAY_1_SOLUTIONS.md)

---

### [Day 2: Event System and Dependency Injection](./DAY_2_EVENTS_AND_DI.md)
**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Difficulty:** ⭐⭐⭐ Intermediate

Master event-driven architecture:
- Shopware's event system
- Creating event subscribers
- Business and lifecycle events
- Service decoration patterns
- Creating custom events
- Advanced DI patterns

**Key Outcomes:**
- Subscribe to system events
- Create custom events
- Decorate existing services
- Implement event-driven features

**✅ Complete Solutions Available:** [DAY_2_SOLUTIONS.md](./SOLUTIONS/DAY_2_SOLUTIONS.md)

---

### [Day 2.5: Storefront Development - Twig & JavaScript](./DAY_2.5_STOREFRONT_DEVELOPMENT.md) 🎨
**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Difficulty:** ⭐⭐⭐ Frontend Focus!

Master Shopware storefront customization:
- Twig template system and inheritance
- Template blocks and extends
- Page objects and variables
- Custom JavaScript plugins
- SCSS/CSS styling
- AJAX and localStorage
- Browser debugging

**Key Outcomes:**
- Extend and customize templates
- Create interactive JavaScript features
- Style with SCSS
- Debug frontend issues

**✅ Complete Solutions Available:** [DAY_2.5_SOLUTIONS.md](./SOLUTIONS/DAY_2.5_SOLUTIONS.md)

---

### [Day 3: Database, Migrations, and Custom Entities](./DAY_3_DATABASE_AND_MIGRATIONS.md)
**Duration:** 2-3 days (14-20 hours with breaks)  
**Difficulty:** ⭐⭐⭐⭐ Advanced - Take your time!

Work with Shopware's Data Abstraction Layer:
- Database migrations
- Entity definitions and collections
- Repository operations (CRUD)
- Complex queries with Criteria API
- Associations and relationships
- Custom fields

**Key Outcomes:**
- Create custom database tables
- Define entities and repositories
- Query data efficiently
- Manage database schema changes

**📝 Solution Outlines Available** - Request complete solutions if needed

---

### [Day 4: API Architecture](./DAY_4_API_ARCHITECTURE.md)
**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Difficulty:** ⭐⭐⭐ Intermediate

Build and consume Shopware APIs:
- Store API vs Admin API
- Creating custom API routes
- Authentication and authorization
- Request/response handling
- API best practices
- OpenAPI documentation

**Key Outcomes:**
- Create Store API endpoints
- Build Admin API controllers
- Implement proper error handling
- Document APIs with OpenAPI

**📝 Solution Outlines Available** - Request complete solutions if needed

---

### [Day 5: Debugging and Error Analysis](./DAY_5_DEBUGGING.md)
**Duration:** 1 day (6-8 hours with breaks)  
**Difficulty:** ⭐⭐ Essential Skills

Master debugging techniques:
- Shopware logging system
- Symfony Profiler
- Xdebug setup and usage
- Error handling strategies
- Performance profiling
- Debugging commands

**Key Outcomes:**
- Debug effectively with Xdebug
- Analyze logs and errors
- Profile performance issues
- Handle exceptions properly

---

### [Day 6: Testing and Caching](./DAY_6_TESTING_AND_CACHING.md)
**Duration:** 5-7 hours

Ensure quality and performance:
- Unit testing with PHPUnit
- Integration testing
- API testing
- Shopware's caching system
- Cache invalidation strategies
- Performance optimization

**Key Outcomes:**
- Write unit and integration tests
- Implement caching strategies
- Optimize performance
- Test coverage analysis

---

### [Day 7: Final Project](./DAY_7_FINAL_PROJECT.md)
**Duration:** 6-8 hours

Build a complete feature from scratch:
- Product Recommendation Engine
- Session tracking
- Database design
- API endpoints
- Caching layer
- Complete test coverage
- Documentation

**Alternative:** [GoTo Webinar Integration](./DAY_7_WEBINAR_INTEGRATION.md) - A comprehensive real-world App example (24-33 hours)

**Key Outcomes:**
- Apply all learned concepts
- Build production-ready feature
- Follow best practices
- Create comprehensive documentation

---

### [Day 8: Shopware Apps (Alternative to Plugins)](./DAY_8_SHOPWARE_APPS.md) 🆕
**Duration:** 4-6 hours  
**Difficulty:** ⭐⭐⭐ Intermediate - Important Architectural Concept

Learn the modern alternative to plugins for external integrations:
- Apps vs Plugins comparison
- Manifest file structure
- OAuth registration flow
- Webhook handling
- Admin API authentication
- Real-world app examples

**Key Outcomes:**
- Understand when to use Apps vs Plugins
- Create and register a Shopware App
- Handle webhooks securely
- Call Shopware's Admin API
- Build loosely-coupled integrations

**Why This Matters:**
Apps are the **preferred approach for:**
- Third-party service integrations (payment, shipping, marketing)
- Multi-shop SaaS solutions
- External microservices
- Marketplace distribution

This complements your plugin knowledge with the modern, API-first approach to Shopware development!

---

## 🛠️ What You'll Build

Throughout this course, you'll build several real-world features:

1. **Learning Plugin** (Days 1-2)
   - Service implementation
   - Event subscribers
   - Configuration system

2. **Product View Tracker** (Days 3-4)
   - Custom database tables
   - Entity definitions
   - Store and Admin APIs

3. **Recommendation Engine** (Day 7)
   - Session-based tracking
   - Affinity calculations
   - Cached API endpoints
   - Complete test suite

4. **Order Logger App** (Day 8)
   - App architecture
   - OAuth registration
   - Webhook processing
   - Admin API integration
   - Signature verification

---

## 📋 Prerequisites

### Required
- PHP 8.1+ installed
- Composer
- Working Shopware 6.5+ installation
- MySQL/MariaDB
- IDE (PHPStorm recommended)
- Basic Git knowledge

### Recommended
- Understanding of Symfony framework
- Object-oriented PHP experience
- REST API concepts
- Basic SQL knowledge
- Command line familiarity

---

## 🚀 Getting Started

### 1. Set Up Your Environment

```bash
# Verify Shopware installation
cd /Users/oliverschafeld/workspace/shopware-experiments/shopware-tutorial-olli
bin/console --version

# Check PHP version
php -v  # Should be 8.1+

# Verify database connection
bin/console dbal:run-sql "SELECT VERSION()"
```

### 2. Start with Day 1

Open [Day 1: Plugin Basics](./DAY_1_PLUGIN_BASICS.md) and follow the step-by-step instructions.

### 3. Complete Exercises

Each day includes exercises to reinforce learning. Complete these before moving to the next day.

### 4. Track Your Progress

Use the checklists at the end of each day to ensure you've covered all topics.

---

## 📖 Learning Approach

### Daily Structure

1. **Theory** (30-45 min) - Understand concepts
2. **Hands-On** (2-4 hours) - Build features
3. **Exercises** (1-2 hours) - Practice independently
4. **Review** (30 min) - Consolidate learning

### Best Practices

- ✅ Code along with examples
- ✅ Complete all exercises
- ✅ Test your code frequently
- ✅ Read linked documentation
- ✅ Ask questions in community
- ✅ Take notes as you learn
- ✅ Commit code regularly

### When You Get Stuck

1. Re-read the relevant section
2. Check the Shopware documentation links
3. Review error logs and messages
4. Use debugging tools (Xdebug, Profiler)
5. Search the Shopware forum
6. Ask in the Shopware Slack community

---

## 🎓 Learning Outcomes

By the end of this course, you'll be able to:

✅ Create Shopware plugins from scratch  
✅ Work with events and the DI container  
✅ Design and implement database schemas  
✅ Build Store and Admin API endpoints  
✅ Debug and troubleshoot effectively  
✅ Write comprehensive tests  
✅ Implement caching strategies  
✅ Follow Shopware best practices  
✅ Build production-ready features  
✅ Document your work professionally  

---

## 📚 Additional Resources

### Official Documentation
- [Shopware Developer Portal](https://developer.shopware.com/)
- [Plugin Development Guide](https://developer.shopware.com/docs/guides/plugins/)
- [API Reference](https://shopware.stoplight.io/docs/store-api/)
- [Core Reference](https://developer.shopware.com/docs/resources/references/core-reference/)

### Community
- [Shopware Forum](https://forum.shopware.com/)
- [Shopware Slack](https://slack.shopware.com/)
- [GitHub Discussions](https://github.com/shopware/platform/discussions)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/shopware6)

### Video Resources
- [Shopware YouTube Channel](https://www.youtube.com/shopware)
- [Shopware Academy](https://academy.shopware.com/)

### Code Examples
- [Shopware Store](https://store.shopware.com/)
- [GitHub - Official Plugins](https://github.com/shopware)
- [Community Plugins](https://github.com/topics/shopware-plugin)

---

## 🏆 Certification Path

After completing this course, consider:

1. **Shopware Certified Developer** exam
2. Contributing to open-source Shopware plugins
3. Building and publishing your own plugins
4. Joining the Shopware partner program

---

## 💡 Tips for Success

### Time Management
- Dedicate 5-7 hours per day
- Take breaks every 90 minutes
- Don't rush through exercises
- Review previous days' material

### Code Quality
- Write clean, commented code
- Follow PSR standards
- Test as you build
- Refactor when needed

### Problem Solving
- Read error messages carefully
- Use debugging tools
- Search before asking
- Learn from mistakes

### Community Engagement
- Share your progress
- Help other learners
- Contribute to discussions
- Build your portfolio

---

## 🔄 Course Updates

This course is based on **Shopware 6.5+**. Key concepts remain stable, but always check:
- [Shopware Release Notes](https://github.com/shopware/platform/releases)
- [Breaking Changes](https://developer.shopware.com/docs/resources/references/upgrades/)
- [Deprecations](https://developer.shopware.com/docs/resources/references/deprecations/)

---

## ✨ Start Your Journey

Ready to become a Shopware expert? 

👉 **[Begin with Day 1: Plugin Basics →](./DAY_1_PLUGIN_BASICS.md)**

---

## 📝 Feedback

This course is designed to be practical and comprehensive. If you have suggestions for improvement:
- Note areas that need more explanation
- Suggest additional exercises
- Share what worked well
- Report any errors or outdated information

---

**Good luck with your Shopware learning journey! 🚀**

*Remember: The best way to learn is by doing. Code along, experiment, and most importantly, have fun building!*
