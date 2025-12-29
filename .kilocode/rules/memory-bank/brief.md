# Virgosoft Limit Order Exchange - Project Brief

## Project Overview
This is a technical assessment project for building a limit-order exchange mini engine with a focus on financial data integrity, concurrency safety, scalable balance/asset management, and real-time systems.

## Core Requirements
- Build a full-stack application with Laravel API backend and Vue.js frontend
- Implement a limit-order exchange system with buy/sell order matching
- Ensure financial data integrity and race condition safety
- Provide real-time updates for order matches and balance changes
- Handle commission calculations (1.5% of matched USD value)

## Key Features
1. User balance and asset management
2. Limit order creation and cancellation
3. Order matching engine (full matches only)
4. Real-time order book updates
5. Commission handling
6. Order history and status tracking

## Technical Constraints
- Backend: Laravel (latest stable)
- Frontend: Vue.js with Composition API
- Database: PostgreSQL (configured in DDEV)
- Real-time: Pusher via Laravel Broadcasting
- Development Environment: DDEV
- Deadline: January 1, 2026 1:12 AM (GMT+4)

## Evaluation Focus
- Balance & asset race safety
- Atomic execution of operations
- Commission calculation accuracy
- Real-time listener stability
- Code quality and repository cleanliness
- Security validation
- Fast setup process
- Meaningful git commits
