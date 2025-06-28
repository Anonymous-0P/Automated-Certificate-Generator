# Automated-Certificate-Generator
Automated Certificate Generator
This is a web application designed to automate the generation of certificates. It provides functionalities for both individual and bulk certificate creation, complete with QR codes for easy validation.
Features

Admin authentication system with login and registration
Bulk upload of certificate data via JSON files
Individual certificate generation through a web form
Automatic generation of QR codes for certificate validation
PDF certificates with custom fonts and layouts
Database storage for certificate records
Validation of certificates using unique codes

Requirements

PHP 7.x or higher
MySQL database
Web server (e.g., Apache, Nginx)
Ghostscript installed on the server

Installation

Clone the repository:
git clone https://github.com/Anonymous-0P/Automated-Certificate-Generator.git


Set up the database:

Create a MySQL database.
Import the db.sql file to create the necessary tables.


Configure the database connection:

Edit db.php with your database credentials.


Ensure Ghostscript is installed:

The application uses Ghostscript for PDF operations. Make sure it is installed and the path is correctly set in the code if necessary.


Set up directory permissions:

Ensure that the following directories are writable by the web server:
qr_codes/
certificates/
images/preview/




Set up a web server:

Configure your web server to serve the PHP files from the repository directory.



Usage

Admin Login:

Access admin_login.php to log in as an administrator.
If you don't have an account, use admin_register.php to create one.


Generate Certificates:

Individual Generation:

Use form.php to input details for a single certificate.
Fill in the required fields such as full name, registration number, organization, course name, dates, etc.
Submit the form to generate the certificate.


Bulk Upload:

Use bulk_upload.php to upload a JSON file containing multiple certificate entries.
Refer to bulkfile-sample.json for the required format.




Validate Certificates:

Each certificate has a unique QR code that links to validate.php?code=<unique_code>.
Scanning the QR code or accessing the URL with the unique code will validate the certificate.



Configuration

Fonts:

The application uses custom fonts: GreatVibes and Cinzel. Ensure they are available in the fonts directory or adjust the code accordingly.


QR Code URL:

The QR code points to a specific URL for validation. If deploying to a different server, update the URL in the code.


Ghostscript Path:

The path to Ghostscript may need to be adjusted based on your server environment. Check the code for the correct path.



Database Schema
The application uses a MySQL database with a table named certificates. The table includes columns such as:



Column Name
Description



full_name
Full name of the certificate recipient


reg_number
Registration number of the recipient


organization_name
Name of the organization


course_name
Name of the course


issue_date
Certificate issue date


start_date
Course start date


end_date
Course end date


total_hours
Total hours of the course


grade
Grade achieved (e.g., O, A, B, C, D, F)


course_content
Description of course content


activities_json
JSON data of activities and marks


unique_code
Unique code for certificate validation


qr_code_path
Path to the generated QR code


pdf_path
Path to the generated PDF certificate


For the exact schema, refer to db.sql.
Security

The application includes CSRF protection. Ensure that the CSRF token is properly handled in forms.
Admin sessions are checked for user agent and IP address for added security.

Dependencies
The application includes the following libraries:

FPDF for PDF generation
PHPQRCode for QR code generation

These are located in the lib directory and do not require separate installation.
Notes

The application is designed to run on a local server or a hosted environment. Make sure to configure the URLs and paths accordingly.
For production use, ensure that all security measures are in place, including secure database credentials and proper session management.
