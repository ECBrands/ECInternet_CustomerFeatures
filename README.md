# Magento2 Module ECInternet CustomerFeatures
``ecinternet/customer_features - 1.3.8.0``

- [Requirements](#requirements-header)
- [Overview](#overview-header)
- [Installation](#installation-header)
- [Configuration](#configuration-header)
- [Specifications](#specifications-header)
- [Attributes](#attributes-header)
- [Notes](#notes-header)
- [Version History](#version-history-header)

## Requirements

## Overview
The EC Internet CustomerFeatures extension assists in syncing customers by adding missing functionality and fields and improving existing Magento 2 customer functionality where needed.

## Installation
- Unzip the zip file in `app/code/ECInternet`
- Enable the module by running `php bin/magento module:enable ECInternet_InstaPAY`
- Apply database updates by running `php bin/magento setup:upgrade`
- Recompile code by running `php bin/magento setup:di:compile`
- Flush the cache by running `php bin/magento cache:flush`

## Configuration

## Specifications
- Disable Customer registration.
- Disable Customer welcome email.
- Set default password for new Customers.
- Limit CustomerGroups which can add new addresses.
- Limit CustomerGroups which can edit existing addresses.
- Enhances new customer notification email.

## Attributes
- `Customer`
  - `ecinternet_company_name`
  - `ecinternet_cust_active_sent`
  - `ecinternet_customer_activated`
  - `ecinternet_is_active`
- `CustomerAddress`
  - `contact_name`

## Notes

## Version History
- 1.3.6.0 - Updated UpgradeData to not show Customer attributes in admin grid.  This could lead to compilation errors for some databases.
