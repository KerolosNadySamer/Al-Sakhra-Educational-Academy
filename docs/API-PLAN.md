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

Response:

```json
{
  "message": "Download allowed.",
  "file_path": "files/example.pdf",
  "download": {}
}
```

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
