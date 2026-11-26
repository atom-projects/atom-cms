# Staff and team applications

Let players apply for a staff rank or a team.

## Create a position

1. Open **Housekeeping > Hotel > Open Positions**.
2. Choose a rank or a team. Ranks come from your emulator. Teams come from Atom.
3. Write the description.
4. Optionally set a start date and an end date.

- No start date: people can apply now.
- No end date: the position stays open.
- With an end date: it closes at that time. Dates use the app time zone (`UTC` by default).

## Handle applications

- Rank applications: use the **Staff Applications** resource.
- Team applications: use the **Team Applications** resource.

What you can see depends on your housekeeping permissions.

## For developers

Positions are in `website_open_positions`:

| Column | Meaning |
| --- | --- |
| `position_kind` | `rank` or `team` |
| `permission_id` or `team_id` | The rank or team |
| `description` | The text |
| `apply_from`, `apply_to` | Start and end date |

Use Housekeeping instead of editing the table. Housekeeping validates the data and clears the caches.
