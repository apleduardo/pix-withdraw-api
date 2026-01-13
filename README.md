# PIX Withdraw API (Hyperf)

## Project Summary
This project is a layered Hyperf PHP API for managing PIX withdraws, including immediate and scheduled operations. It features pessimistic locking, transactional safety, real email notifications via Mailhog, and full test coverage (unit/integration). The system is ready for Docker-based development and includes a default account for quick testing.

## Technologies Used
- PHP 8.3
- Hyperf Framework
- Docker & Docker Compose
- MySQL (or compatible)
- Symfony Mailer (SMTP)
- Mailhog (email testing)
- PHPUnit & Mockery (testing)

## How to Run the Application
1. **Clone the repository**
2. **Start the environment:**
   ```bash
   docker-compose up --build
   ```
3. **Run migrations:**
   ```bash
   docker-compose exec api php bin/hyperf.php migrate
   ```
   This will create tables and insert a default account with balance for testing.

## How to Test a Withdraw Request
You can use curl or Postman to test the withdraw endpoint. Example using the default account:

- **Endpoint:** `POST /account/00000000-0000-0000-0000-000000000001/balance/withdraw`
- **Payload:**
  ```json
  {
    "method": "PIX",
    "pix": { "type": "email", "key": "your@email.com" },
    "amount": 100.00
  }
  ```
- **Curl Example:**
  ```bash
  curl -X POST http://localhost:9501/account/00000000-0000-0000-0000-000000000001/balance/withdraw \
    -H "Content-Type: application/json" \
    -d '{"method":"PIX","pix":{"type":"email","key":"your@email.com"},"amount":100.00}'
  ```

## How to Access Database and Email System
- **Database:**
  - Default: MySQL running in Docker (see `docker-compose.yml` for credentials)
  - You can connect using any MySQL client to the exposed port.
- **Mailhog (Email):**
  - Access Mailhog web UI at [http://localhost:8025](http://localhost:8025)
  - All emails sent by the API will appear here for testing.

## How to View Application Logs
- **Logs are written to:** `runtime/logs/hyperf.log`
- You can view logs with:
  ```bash
  docker-compose exec api tail -f runtime/logs/hyperf.log
  ```

## Running Tests
- **Unit and integration tests:**
  ```bash
  docker-compose exec api vendor/bin/phpunit
  ```

---
For more details, see the code and comments. For any issues, check the logs and Mailhog for troubleshooting.
