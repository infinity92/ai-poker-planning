# Poker planning app

## About this project

This project was fully generated by AI.

Poker Planning App is a collaborative tool designed for agile teams to estimate tasks using the Planning Poker technique. Users can create or join rooms, add tasks, vote on task estimates, reveal votes, and revote if needed. The app supports real-time collaboration for distributed teams.

## Features
- Create and join planning rooms
- Add tasks for estimation
- Vote on tasks using poker cards
- Reveal and discuss votes
- Revote if consensus is not reached

## Get Started

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd poker-planning
   ```
2. Copy .env.example to .env and update values if necessary.
3. Start the application using Docker Compose:
   ```bash
   docker-compose up --build
   ```
4. Open your browser and go to `http://localhost` to access the app.
5. Initialize the database by opening http://localhost/setup_db.php in your browser or using curl.

For more details, see the `docker/` directory and configuration files.

## Database Setup

The project uses MySQL as the database. All required environment variables are set in the `.env` file. After starting the containers, run the setup_db.php script to initialize the database.

In the file `public/setup_db.php`, make sure to set the actual values for database connection parameters (`$dbHost`, `$dbName`, `$dbUser`, `$dbPass`, `$dbRootPass`) according to your environment or the values specified in your `.env` file or Docker configuration.

## Environment Variables

The `.env` file should contain:

```
MYSQL_ROOT_PASSWORD=
MYSQL_DATABASE=
MYSQL_USER=
MYSQL_PASSWORD=
```

You can use `.env.example` as a template.

## WebSocket Port Configuration

In the file `public/index.php`, the WebSocket connection is established with this line:

```js
ws = new WebSocket(`ws://${window.location.hostname}:8181`);
```

If you change the WebSocket server port in your Docker configuration (for example, in `docker-compose.yml`), you must update the port number in this line to match the port specified in Docker. Otherwise, the client will not be able to connect to the WebSocket server.
