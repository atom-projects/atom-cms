# Username filtering

Block unwanted words in new usernames.

1. In Housekeeping, under CMS settings, set `website_wordfilter_enabled=1`.
2. Add the blocked words to the `website_wordfilter` table.

Atom rejects a username that contains a blocked word anywhere. It also rejects an exact match.

Before you add a short word, test it. A short word can block good names too.

Existing usernames do not change. This filter is separate from the emulator's in-game word filter. It works without it.
