
- Init project
- Import libraries
- Create model Project
- Copilot Command:
```
This is a simple application. This application is to track projects. Achitecture includes backend as laravel-rest-api and frontend vue. Vue must access resources via rest-api. Complete the API functions are List (paginated), create, edit, and delete.

Refer to the migration for the attributes of the project model. 

Code achitecture must-haves:
- resource
- jsonresponse
- - validations accordnigly:
``` 
Client Name is required.
Project Name is required.
Status must be valid.
Priority must be valid.
Due Date cannot be earlier than Start Date.
Invalid requests should return meaningful errors.
```

List function:
- Search functionality
- Filtering by Status
- Filtering by Priority

Note that Vue is not yet being implemented right now, but the code generated are expected to be used by Vue.
```
- API resource, controller, and route


