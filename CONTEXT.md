# Concert Ticketing System — Technology Stack Specification

## Objective

Build a **Concert Ticketing System** according to the provided business requirements while **strictly using the technology stack defined below**.

The implementation must prioritize:

- High Performance
- Security
- Reliability
- Maintainability
- Scalability
- Clean Architecture
- Production-ready code

Do **NOT** replace any technology unless explicitly instructed.

---

# Technology Stack

## Frontend

- Next.js 15
- React 19
- TypeScript
- Tailwind CSS
- shadcn/ui
- Zustand
- TanStack Query

### Responsibilities

- Responsive UI
- Authentication UI
- Concert browsing
- Seat selection
- Booking flow
- Payment flow
- Ticket management
- Organizer dashboard
- Admin dashboard

---

## Interactive Seat Map

Use

- HTML5 Canvas

Requirements

- Interactive seat selection
- Zoom
- Pan
- Hover effect
- Color-coded seat status
- Fast rendering
- Support thousands of seats
- No HTML grid rendering
- No rendering every seat as individual DOM nodes

---

## Backend

Use

- NestJS

Architecture

- Modular
- Clean Architecture
- REST API
- WebSocket

Modules

- Authentication
- Users
- Concert
- Seat
- Booking
- Payment
- Ticket
- Organizer
- Admin

---

## API

Use

- REST API
- WebSocket

REST

Used for

- Authentication
- CRUD
- Booking
- Payment
- Ticket

WebSocket

Used only for

- Seat locking
- Seat releasing
- Seat booking
- Real-time dashboard updates

Do NOT poll the server continuously.

---

# Database

Use

- PostgreSQL

Use Prisma ORM.

Requirements

- ACID Transaction
- Foreign Keys
- Database Constraints
- Indexes
- Optimistic/Pessimistic locking where appropriate

Never store business data inside Redis.

Business data must always be stored in PostgreSQL.

---

# ORM

Use

- Prisma

Responsibilities

- Migration
- Type-safe Query
- Database Schema

---

# Redis

Redis is required.

Use Redis for:

## Seat Lock

Implement temporary seat locking.

Requirements

- TTL = 10 minutes
- Automatic expiration
- Prevent duplicate booking
- Atomic locking

Recommended command

SET seat:{seatId} {userId} NX EX 600

---

## Cache

Cache

- Concert list
- Concert detail
- Seat availability

Do NOT cache booking data.

---

## Rate Limiting

Protect

- Login
- Booking
- Payment

---

## Refresh Token Storage

Store refresh tokens inside Redis.

---

# Queue

Use

- BullMQ

BullMQ must use Redis.

Queue Responsibilities

- QR Code generation
- Email sending
- Release expired seat lock
- Notification
- Report generation

Do not execute these tasks synchronously.

---

# Authentication

Use

- JWT
- Refresh Token

Authorization

RBAC

Roles

- Guest
- Customer
- Organizer
- Admin

Passwords

- bcrypt

Never store plaintext passwords.

---

# Storage

Use

- Cloudflare R2

Store

- Concert posters
- QR Codes
- Generated assets

Never store uploaded files inside the application container.

---

# QR Code

Use

- qrcode

Requirements

Every ticket must contain

- Ticket ID
- Booking ID
- Concert ID

QR Code must be unique.

Each QR Code can only be scanned once.

---

# State Management

## Zustand

Use only for client-side state.

Examples

- Selected seats
- Shopping cart
- Logged-in user
- UI state

Never store server data inside Zustand.

---

## TanStack Query

Use only for server state.

Examples

- Concerts
- Seats
- Booking history
- User profile
- Organizer dashboard

Use

- Query cache
- Mutation
- Invalidation
- Background refetch

---

# Monitoring

Use

- Prometheus
- Grafana

Monitor

- API latency
- Redis
- PostgreSQL
- Queue
- Memory
- CPU

---

# Deployment

Use

- Docker
- NGINX

Architecture

NGINX

↓

NestJS

↓

PostgreSQL

↓

Redis

---

# CI/CD

Use

- GitHub Actions

Pipeline

- Install
- Lint
- Test
- Build
- Docker Build
- Deploy

---

# Performance Requirements

The implementation must satisfy

- Support at least 200 concurrent users
- Seat map loads within 2 seconds
- API response under 500ms (excluding external services)
- Booking transaction under 3 seconds
- WebSocket latency under 1 second

---

# Security Requirements

Implement

- JWT Authentication
- Role-Based Authorization
- Helmet
- CORS
- Input Validation
- SQL Injection Protection
- XSS Protection
- CSRF Protection (if cookies are used)
- Rate Limiting

Never trust client-side validation.

---

# Booking Rules

A seat can only belong to one booking.

Seat selection flow

Available

↓

Locked

↓

Booked

or

↓

Expired

↓

Available

Never allow duplicate booking.

---

# Payment Rules

Payment Success

- Confirm booking
- Mark seats as booked
- Generate E-Ticket
- Generate QR Code
- Send confirmation

Payment Failed

- Cancel booking
- Release seat lock
- Notify client

---

# Logging

Log

- Login
- Booking
- Payment
- Ticket Validation
- Admin Activity
- Organizer Activity

Do not log passwords or sensitive personal information.

---

# Development Constraints

The AI agent must:

- Follow the specified technology stack exactly.
- Do not replace libraries with alternatives.
- Keep the architecture modular.
- Use TypeScript everywhere.
- Write clean, maintainable, production-ready code.
- Follow SOLID principles.
- Use dependency injection in NestJS.
- Use Prisma migrations.
- Use Redis only for ephemeral data.
- Use PostgreSQL for persistent business data.
- Implement proper error handling.
- Implement proper transaction management.
- Implement optimistic or pessimistic locking where necessary.
- Write reusable components.
- Avoid duplicated logic.
- Avoid hardcoded values.
- Use environment variables for configuration.

The implementation should be production-ready and extensible for future features without major architectural changes.