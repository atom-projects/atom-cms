# Account limits

Limit how many accounts one IP address can register.

1. In Housekeeping, under CMS settings, set `max_accounts_per_ip`.
2. Set up [trusted proxies](1.-Installing-Atom-CMS.md#production-configuration) first. Without them, Atom cannot see real visitor addresses.

People in the same home or network share one IP address. They count toward the same limit.

Test one registration under the limit and one at the limit. Use a throwaway database.

To close registration or ask for invitation codes, see [registration controls](Registration-limiting.md).
