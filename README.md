# MTECH Shortcodes


## Initial setup
- Test custom role
- Remove API tab
- Turn shortcodes tab into "schedules" tab

### Shortcodes data restructure
- Define db table requirements
    What files need to change?
    - database.php (refactor shortcodes table)
    - form-handlers.php (POST fields will change)
    - tab-schedules.php (dashboard shortcodes frontend. Needs to be updated for saving new 4 fields)
- Update generate db table code

### Add new rows without page refresh

### Remove rows without page refresh
- Delete checkboxes -> mark schedule items to be deleted upon saving

### Misc
- Remove program slug from schedules tab

### HTML table template