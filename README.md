# ScandiPWA ProductAlertsGraphQl

Fork of [scandipwa/product-alerts-graphql](https://github.com/scandipwa/product-alerts-graphql) 1.0.1, maintained by Selveq for Magento 2.4.9 and PHP 8.3. Module name and namespace are unchanged, and the package replaces `scandipwa/product-alerts-graphql` at every version, so it installs as a drop-in replacement. Selveq is not affiliated with or endorsed by Scandiweb.

## What it does

- Adds `productAlertSubscribe(productId: ID!, type: String!): Boolean`, which subscribes the signed-in customer to a price-drop or back-in-stock alert for a product.
- Takes the two type values the ScandiPWA theme sends, `PRODUCT_ALERT_PRICE_DROP` and `PRODUCT_ALERT_IN_STOCK`, and records the product's final price at subscribe time as a price alert's threshold.
- Updates the alert already on file for that customer, product and store rather than adding a second one, so subscribing twice leaves one alert.
- Answers a guest, an unknown product and an unknown type as `graphql-authorization`, `graphql-no-such-entity` and `graphql-input`, so a client can tell the three apart.

## Install

```sh
composer require selveq/product-alerts-graphql
bin/magento setup:upgrade
```

The storefront shows its alert buttons only when `selveq/store-graphql` publishes the `product_alert_allow_price` and `product_alert_allow_stock` settings; the mutation itself needs nothing else.

## License

[OSL-3.0](LICENSE), the license of the original work. Scandiweb's copyright notices are kept in every file, and each file Selveq changed carries a `Modifications © Selveq` notice.
