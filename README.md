# PIX Withdraw API (Hyperf)

## Project Instructions

For detailed project definitions, requirements, and business rules, see the [INSTRUCOES.md](https://github.com/apleduardo/pix-withdraw-api/blob/main/api/INSTRUCOES.md) file in the repository.

---

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
   ```bash
   git clone git@github.com:apleduardo/pix-withdraw-api.git
   cd pix-withdraw-api
   ```
2. **Copy the environment file**
   ```bash
   cp .env.example .env
   ```
   Adjust any values in `.env` as needed for your environment.
3. **Start the environment:**
   ```bash
   docker compose up --build
   ```
4. **Run migrations:**
   ```bash
   docker compose exec hyperf php bin/hyperf.php migrate
   ```
   This will create tables and insert a default account with balance for testing.

## Environment Setup
Before starting the application, copy `.env.example` to `.env` and adjust any values as needed for your environment:

```bash
cp .env.example .env
```

- The default values in `.env.example` are ready for Docker Compose usage.
- Set your `API_AUTH_TOKEN` in `.env` for authentication.

## Authentication (Token)
All requests require a Bearer token in the `Authorization` header. The token value is defined in the `.env` file:

```
API_AUTH_TOKEN=changeme
```

**How to send the token:**
- Add the header:
  ```
  Authorization: Bearer changeme
  ```
- If the token is missing or incorrect, the API will return `401 Unauthorized`.

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
    -H "Authorization: Bearer changeme" \
    -d '{"method":"PIX","pix":{"type":"email","key":"your@email.com"},"amount":100.00}'
  ```

## How to Test a Scheduled Withdraw Request
You can use curl or Postman to test a scheduled withdraw. Example using the default account:

- **Endpoint:** `POST /account/00000000-0000-0000-0000-000000000001/balance/withdraw`
- **Payload:**
  ```json
  {
    "method": "PIX",
    "pix": { "type": "email", "key": "your@email.com" },
    "amount": 100.00,
    "schedule": "2026-01-20 10:00:00"
  }
  ```
- **Curl Example:**
  ```bash
  curl -X POST http://localhost:9501/account/00000000-0000-0000-0000-000000000001/balance/withdraw \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer changeme" \
    -d '{"method":"PIX","pix":{"type":"email","key":"your@email.com"},"amount":100.00,"schedule":"2026-01-30 10:00:00"}'
  ```

## How to Process Scheduled Withdraws
To process scheduled withdraws, run the following command inside the running Hyperf container:

```bash
docker compose exec hyperf php bin/hyperf.php withdraw:process-scheduled
```

This will execute all scheduled withdraws that are due.

## How to Add a New Withdraw Type (PIX Key)
This project uses the Open/Closed principle (SOLID) and the Strategy pattern for PIX key types. To add a new type (e.g., CPF):

1. **Create a new handler class:**
   - Implement `App\Service\PixKeyHandlerInterface`.
   - Example: `PixCpfHandler` for CPF validation and processing.
2. **Register the handler:**
   - Add the new handler to `App\Service\PixKeyHandlerFactory::$handlers`.
3. **Update validation (optional):**
   - Add the new type to the validation rule in the controller if needed.

No changes are required in the controller or main service logic. Each handler is responsible for its own validation and processing.

Example handler:
```php
class PixCpfHandler implements PixKeyHandlerInterface {
    public function validate(array $data): ?string {
        // CPF validation logic
    }
    public function process(string $accountId, array $data): array {
        // CPF withdraw logic
    }
}
```

This makes the system easy to extend for new PIX key types or future withdraw methods.

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
  docker compose exec hyperf tail -f runtime/logs/hyperf.log
  ```

## Running Tests
- **Unit and integration tests:**
  ```bash
  docker compose exec hyperf composer test
  ```
