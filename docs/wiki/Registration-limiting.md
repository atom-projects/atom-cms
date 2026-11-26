# Registration controls

## Close registration

In Housekeeping, under CMS settings, set `disable_registration=1`. New people can no longer register. Set it to `0` to open registration again.

## Invitation codes (beta codes)

Use invitation codes to keep registration open for invited people only.

1. Set `requires_beta_code=1`.
2. Add a row to the **`website_beta_codes`** table:
   - **`code`**: a unique code that is hard to guess
   - **`user_id`**: leave it empty (`null`)
3. Give each person their own code.

A code works once. After a successful registration, Atom saves the new user's ID in `user_id`. A code with a `user_id` is used.

To limit accounts per IP address, see [account limits](Account-limitation.md).
