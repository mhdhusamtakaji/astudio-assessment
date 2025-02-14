# ASTUDIO Assessment
* MHD HUSAM TAKAJI

Welcome to the ASTUDIO Assessment repository. This project demonstrates a Laravel-based application for managing Projects, Timesheets, and dynamic (EAV) attributes, complete with authentication and filtering.

## Quick Overview

- **Framework**: Laravel
- **Authentication**: Passport (Token-based)
- **Key Features**: EAV for Projects, Filtering, Timesheets, Many-to-Many relationship between Users and Projects

## Detailed Documentation

For full setup instructions, usage details, and API specifications:
- **See [readme.pdf](./docs/readme.pdf)** in this repository.  
  *(If the link doesn’t work in your environment, just find `docs/readme.pdf` at the root.)*

## Postman Files

To test this application quickly using Postman:
- The **Postman collection** (`./docs/postman/ASTUDIO Assessment API.postman_collection.json`) and **environment** file (`./docs/postman/ASTUDIO Assessment Enviroment.postman_environment.json`) are located in the [`./docs/postman/`](./docs/postman/) folder.  
- Import them into Postman, select the environment, and update `{{base_url}}` if necessary.

## .env File

A copy of the `.env` file (or `.env.example`) is included for convenience:
- **`.env.example`** is at the project root.  
- After cloning, rename it to **`.env`** and update the required values (DB credentials, etc.) before running the application.

---

**Thank you for checking out this repository!** For any questions, refer to the detailed `readme.pdf` or feel free to contact the project maintainer.
