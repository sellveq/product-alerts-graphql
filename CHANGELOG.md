# Changelog

## 2.0.0

Forked from `scandipwa/product-alerts-graphql` 1.0.1. Module name and namespace are unchanged, and the package replaces `scandipwa/product-alerts-graphql` at every version, so it installs as a drop-in replacement.

- The two alert models are created per request, so two subscribes aliased into one GraphQL request each persist instead of the second overwriting the first.
- A product that does not exist is answered as not-found rather than as a temporary failure, and a genuine failure is logged before the generic refusal.
- The customer-facing failure message is Magento's own wording again, with `could n't` corrected.
- An unknown `type` is refused with a message naming the two accepted values, instead of quoting back what the caller sent.
- `ProductAlertSubscribe.php` carries the Adobe copyright notice of the core controllers whose `execute()` bodies it copies.
- `setup_version` is gone from `module.xml`, which declared a schema version for a module that ships no setup scripts.
