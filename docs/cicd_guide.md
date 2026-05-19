
---

## `docs/cicd_guide.md`

```md
# CI/CD Guide

TradeSphere uses CI/CD through GitHub Actions and Azure App Service.

## What is CI/CD?

CI/CD means Continuous Integration and Continuous Deployment.

In this project, whenever new code is pushed to GitHub, Azure automatically deploys the latest version of the website.

## CI/CD Flow

```text
Developer updates code
↓
git add .
↓
git commit
↓
git push
↓
GitHub Actions starts automatically
↓
Azure deploys the updated website
↓
Live website is updated