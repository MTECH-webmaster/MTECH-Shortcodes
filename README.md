# MTECH Coursedog

The MTECH Coursedog plugin provides a shortcode interface with the Coursedog Curriculum API.

## Shortcodes

### Types

- cost
- program_length
- program_length_mobile
- certs
- registration_range
- registration_table
- prereqs
- materials_required
- materials_optional

### Example usage

In the following example, "electrical" is the program slug set in the dashboard and "cost" is the shortcode type:
```
[mtech-shortcodes program_slug="electrical" type="cost"]
```

## Settings

The MTECH Coursedog settings page allows for the following configurations:

### API tab

- Setting API credentials (a username and password for a Coursedog account with API access).

### Shortcodes tab

- Adding, editing and removing programs
- Adding, editing and removing shortcodes
- Deleting shortcodes transients

#### Program slug

This value corresponds to the program_slug value in the shortcode instances.

![Screenshot of the MTECH Coursedog dashboard showing Electrical Apprenticeship config fields](/docs/dashboard-example.png)

#### Coursedog Program ID
When there is no Program ID value set, the plugin queries the API by search, using the program's name as the query.
```
http://api/v1/cm/schoolId/programs/search/searchQuery
```
When a Program ID value is set, the plugin will query the API for a program with that exact ID.
```
http://api/v1/cm/schoolId/programs/programId
```
Program ID values can be found by querying the Coursedog Curriculum API for all programs.
```
http://api/v1/cm/schoolId/programs?limit=50
```
