# API Plan

Base URL: `http://127.0.0.1:8000/api`

## Authentication Flow

### Login

`POST /login`

```json
{
  "login": "admin@alsakhra.local",
  "password": "password",
  "device_name": "Chrome Windows",
  "device_identifier": "unique-device-id"
}
```

Response:

```json
{
  "user": {},
  "token": "1|sanctum-token"
}
```

Use the token with:

`Authorization: Bearer {token}`

### Current User

`GET /me`

### Logout

`POST /logout`

## Organization Creation Flow

`POST /admin/organizations`

```json
{
  "name": "Future Center",
  "type": "center",
  "phone": "01000000000",
  "email": "center@example.com"
}
```

Creates in one transaction:

- Organization
- Wallet
- Default commission contract
- Student registration code
- QR code metadata

## Student Registration Flow

`POST /student/register`

```json
{
  "name": "Ahmed Mohamed",
  "phone": "01000000000",
  "password": "123456",
  "registration_code": "CTR-45872",
  "grade_id": 1
}
```

Flow:

- Validate active registration code
- Resolve organization
- Create user
- Create student educational profile
- Assign `student` role
- Mark code as used
- Return Sanctum token

## Registration Codes

### Generate

`POST /codes/generate`

```json
{
  "organization_id": 1,
  "type": "student",
  "expires_at": "2026-12-31 23:59:59"
}
```

### Validate

`POST /codes/validate`

```json
{
  "code": "CTR-45872"
}
```

### QR Metadata

`GET /codes/{id}/qr`

## Courses

### List

`GET /courses`

### Create

`POST /courses`

```json
{
  "organization_id": 1,
  "teacher_id": 2,
  "subject_id": 1,
  "grade_id": 1,
  "title": "Math Grade 1",
  "description": "Course description",
  "price": 250
}
```

### Update

`PUT /courses/{id}`

### Delete

`DELETE /courses/{id}`

## Teachers

`GET /teachers`

`POST /teachers`

```json
{
  "organization_id": 1,
  "name": "Teacher Name",
  "email": "teacher@example.com",
  "phone": "01000000001",
  "password": "123456",
  "role": "teacher",
  "subject_ids": [1],
  "grade_ids": [1]
}
```

`GET /teachers/{id}`

`PUT /teachers/{id}`

`DELETE /teachers/{id}`

## Students

`GET /students`

`POST /students`

```json
{
  "organization_id": 1,
  "grade_id": 1,
  "name": "Student Name",
  "phone": "01000000002",
  "password": "123456",
  "parent_phone": "01000000003"
}
```

`GET /students/{id}`

`PUT /students/{id}`

## Lessons

`POST /lessons`

```json
{
  "course_id": 1,
  "title": "Lesson 1",
  "description": "Intro",
  "order_number": 1,
  "is_free": false
}
```

## Exams

`POST /exams`

```json
{
  "course_id": 1,
  "title": "First Exam",
  "duration_minutes": 30,
  "total_marks": 20,
  "is_active": true
}
```

## Questions

Supports:

- `mcq`
- `true_false`
- `essay`

`POST /questions`

```json
{
  "exam_id": 1,
  "question_type": "mcq",
  "question_text": "Choose the correct answer",
  "marks": 2,
  "order_number": 1,
  "answers": [
    {"answer_text": "A", "is_correct": true},
    {"answer_text": "B", "is_correct": false}
  ]
}
```

## PDF Protection

`GET /lesson-files/{id}/download`

Checks:

- Student enrollment
- `allow_download`
- `download_limit`
- `expiry_days`
- Existing `file_downloads` record
- `max_downloads`
- `download_expiry_days`

Response:

```json
{
  "message": "Download allowed.",
  "file_path": "files/example.pdf",
  "download": {}
}
```

## Video Security

`GET /videos/{id}/signed-url`

Returns a short-lived stream URL:

```json
{
  "provider": "bunny",
  "expires_in": 300,
  "stream_url": "https://video-domain.com/video/123.m3u8?token=...&expires=..."
}
```

The backend checks enrollment before issuing the URL. Configure:

```env
VIDEO_STREAM_BASE_URL=https://video-domain.com
VIDEO_SIGNING_SECRET=
```

## Device Management

`POST /device/check`

```json
{
  "device_name": "Chrome Windows",
  "device_identifier": "unique-device-id",
  "max_devices": 2
}
```

The API allows the first two active devices by default and rejects the third.

## Books Store

### List Books

`GET /books`

### Create Book

`POST /books`

```json
{
  "organization_id": 1,
  "title": "Math Sheet",
  "description": "PDF book",
  "pdf_file": "books/math.pdf",
  "price": 100,
  "status": "active"
}
```

### Purchase Book

`POST /books/{id}/purchase`

```json
{
  "payment_id": 1
}
```

## Payment Receipts

`POST /payment-receipts`

```json
{
  "payment_id": 1,
  "payment_method_id": 1,
  "receipt_path": "receipts/1.png",
  "transaction_reference": "TX-123"
}
```

`PUT /payment-receipts/{id}`

```json
{
  "status": "approved",
  "notes": "Verified"
}
```

## Audit Logs

Important actions write to `audit_logs`, including:

- Device check
- Video signed URL creation
- PDF download
- Book creation and purchase
- Payment receipt create/update

## Wallet And Payments

Services:

- `CommissionService`
- `PaymentService`
- `WalletService`

Flow:

- Calculate commission percentage
- Split platform and owner amounts
- Store payment
- Credit organization wallet
- Store wallet transaction

### Payments

`GET /payments`

`POST /payments`

```json
{
  "student_id": 5,
  "course_id": 1,
  "payment_method": "cash",
  "status": "paid"
}
```

`PUT /payments/{id}`

```json
{
  "status": "refunded"
}
```

### Wallets

`GET /wallets`

`GET /wallets/{id}`

### Withdraw Requests

`GET /withdraw-requests`

`POST /withdraw-requests`

```json
{
  "amount": 1000,
  "notes": "Monthly withdrawal"
}
```

`PUT /withdraw-requests/{id}`

```json
{
  "status": "paid",
  "notes": "Transferred"
}
```

### Audit Logs

`GET /audit-logs`

Optional filter:

`GET /audit-logs?action=student_created`
