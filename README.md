# KiskBase

Smart knowledge base app that blends textual and tabular data. And checklists.

## Features

- Every KB entry is question + answer.
- Any list can be instantly converted into a checklist.
- Use DB tables:
	- Put a *SELECT statement* anywhere and its result will be shown right there.
	- Insert and edit table values right in the app.
	- Access tables via API.
- Autocomplete for hashtags and people.
- Questions are automatically recognized and made into links (wiki-style).
- Fulltext search (obviously).
- Login with Google account.

## Limitations

- You have to define data tables outside of the app (for now).
- Tabular data manipulation is limited.
- Only simple SQL fulltext search (for now).
- Documentation is nonexistent (for now).

> If you are not embarrassed by the first version, you've launched too late. (They say.)

## Installation

You need a server environment with PHP & MySQL.

1. Clone repo
2. Get dependencies via composer and bower
3. Rename config.blank.neon to config.neon and fill the google & database info
4. Set documentroot to path/to/app/www/
5. Run /setup and click that button
6. That should do it! (yes, these instructions are very brief, sorry)
