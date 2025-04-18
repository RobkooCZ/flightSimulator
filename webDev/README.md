### Configuration Instructions

To make the website work, create a `.env` file in the root directory of `webDev` and define the following variables:

```plaintext
# Database Configuration
DB_HOST=                         # The hostname of your database server (e.g., localhost or an IP address)
DB_NAME=                         # The name of your database
DB_USER=                         # The username for your database
DB_PASS=                         # The password for your database

# Table Names
DB_USER_TABLE=                   # The table for storing user information
DB_USER_PREFERENCES_TABLE=       # The table for storing user preferences
DB_USER_LOGS_TABLE=              # The table for storing user logs
DB_LEADERBOARD_TABLE=            # The table for storing leaderboard data

# Application Settings
APP_TIMEZONE=                    # The timezone for your application (e.g., Europe/Prague)
MIN_LOG_LEVEL=                   # The minimum log level (e.g., DEBUG, INFO, WARNING, ERROR, NONE)