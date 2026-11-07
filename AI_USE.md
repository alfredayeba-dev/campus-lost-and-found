# Statement on the Use of Artificial Intelligence

**Project:** Campus Lost and Found System
**Author:** Alfred Owatarite Ayeba-Enenimiete
**Institution:** Nile University 
**Course:** SEN100 Software Engineering
**Date of statement:** November 2026

---

## 1. Purpose of this document

This document discloses how artificial intelligence (AI) was used in the design, development, testing, deployment and documentation of the Campus Lost and Found System. It describes which tool was used, what it contributed, what the author contributed, and how AI-generated material was reviewed before being adopted. It is intended to support transparency and academic integrity.

## 2. Summary

| Item | Detail |
|---|---|
| **AI tool** | Claude, an AI assistant developed by Anthropic and Copilot, an AI assistant developed by Microsoft |
| **Access method** | The claude.ai chat interface (text and screenshot uploads) |
| **Period of use** | 3 November 2026 to 7 November 2026 |
| **Nature of use** | Code drafting and revision, debugging assistance, security and deployment guidance, explanation of concepts, and drafting of documentation |
| **Where AI was not used** | Setting up and operating the author's accounts, running and testing the code, making deployment changes, and all final decisions |

In brief: the author conceived the project, supplied the requirements and design direction, ran and tested every piece of code on a real system, diagnosed faults using server logs and screenshots, and made the final decisions. The AI assisted by proposing and revising code, explaining the reasoning behind it, and answering questions as problems arose. The AI never had access to the author's computer, accounts, database or hosting.

## 3. Division of work

| Component | Author's contribution | AI contribution |
|---|---|---|
| Project concept and scope | Chose the topic (campus lost and found) and the feature direction | Suggested a roadmap of possible features and the order to build them |
| Database tables | Created the initial `users` and `items` tables in phpMyAdmin | Proposed additional columns and tables (verification, reset, rate limiting) and wrote the consolidated `schema.sql` |
| Registration, login and dashboard (first versions) | Wrote the first working versions | Reviewed them, identified security weaknesses (SQL injection, unvalidated input) and rewrote them with prepared statements, validation and error handling |
| Visual design | Selected the retro "Mac Magic" template as the style reference and directed every layout change (card sizes, spacing, hero image, hiding window titles, logout placement, and so on) | Wrote the CSS and page markup implementing the chosen style |
| Lost and found reporting, browsing, search, My Posts | Specified the behaviour and tested it | Wrote the initial code |
| Item editing | Wrote and tested the first-verion | Reviewed and retested by copilot |
| Profile page | Wrote the first version, including the password-change form | Reviewed and rewrote it (validation, form persistence, session updates, email re-verification) |
| Admin area | Requested it and tested it | Wrote the admin pages, access guard and user management |
| Security hardening | Decided to adopt CSRF protection, CAPTCHA, email verification, password reset and rate limiting | Designed and wrote the implementations and explained how each works |
| Cloud services | Created the accounts (TiDB Cloud, Cloudinary, Brevo, Cloudflare, Render), obtained and stored the keys, and configured the services | Recommended the architecture and wrote the integration code (database over TLS, signed image upload, email API, Dockerfile) |
| Debugging | Collected logs and screenshots, applied fixes and reported results | Interpreted error messages and proposed fixes |
| Documentation | Reviewed and approved the content | Drafted `README.md` and this statement from the project's history |

## 4. Summary of AI assistance by phase

1. **Foundations.** Review of the author's first PHP pages; rewrite using prepared statements and session handling; creation of the item-reporting, browsing and "My Posts" features.
2. **Interface.** Implementation of a retro, black-and-white interface from a reference template; later adjustments directed by the author (image sizes, card layout, spacing, navigation).
3. **Feature extensions.** Dashboard statistics, single-item page, admin area, profile page.
4. **Security.** CSRF tokens, session hardening, Cloudflare Turnstile CAPTCHA, email verification, password reset, login rate limiting, protection of private files.
5. **Deployment.** Selection of free hosting services, environment-variable configuration, Docker container definition, migration of image storage to Cloudinary and email to an HTTPS API, database migration to TiDB Cloud.
6. **Documentation.** README and this statement.

For deployment guidance the assistant used web searches to check current facts about the hosting and email providers (for example, that the free hosting tier blocks outbound SMTP ports and has a temporary disk).

## 5. How AI output was reviewed and verified

AI output was treated as a draft and not as authoritative. The author:

1. **Ran every change** in a local XAMPP environment, and later against the real cloud services, before deployment.
2. **Tested behaviour deliberately,** including negative cases (a form submitted with its security token removed, repeated wrong passwords, reused and expired links, access to admin pages as a student, direct requests to private files). The test cases are listed in Section 13 of the README.
3. **Used evidence to resolve faults,** namely the Apache error log, browser developer tools, database tables and screenshots.
4. **Asked for explanations** of code and security concepts and for step-by-step breakdowns, so that the author could understand and later reproduce the work.
5. **Overrode or redirected the AI** when its suggestion did not match what the author wanted.

### Errors and limitations in AI output that were found and corrected

AI output contained mistakes, which is why verification mattered. Examples encountered in this project:

| Issue | How it was found | Resolution |
|---|---|---|
| A CSS rule meant for stacked windows pushed all but the first dashboard card out of alignment | Screenshot of the dashboard | The rule was removed and spacing applied directly where needed |
| Instructions for exporting a database "structure only" referred to an option that did not exist in the author's phpMyAdmin version | The author could not find the option and reported it with screenshots | Alternative export methods were given and, later, a hand-written schema replaced the export |
| A database certificate path that was valid on Linux but not on the author's Windows machine | Apache error log (`failed loading cafile`) | The configuration was changed to search for a valid certificate file on each platform |
| A phpMyAdmin dump did not create the tables on the cloud database | "Table does not exist" error | A clean, hand-written `schema.sql` compatible with both databases was produced |
| Multi-step patch instructions for the profile page were hard to follow and a line was pasted into the wrong file | The author asked for clarification | Complete replacement files were supplied instead of partial edits |

These examples show that the code was not adopted blindly. The security-related code was written by the AI and tested by the author, but it has **not** been independently audited or penetration-tested.

## 6. What the AI did not do

- It did not have access to the author's computer, files, accounts, database or hosting dashboard.
- It did not execute the author's code, deploy the application or enter any credentials.
- It did not make decisions about scope, design or submission. These remained with the author.

## 7. Data and secrets handling

- Passwords, API keys and database credentials are stored in environment variables or in a local `.env` file that is excluded from version control and from the production container image.
- The repository contains no real credentials.
- Screenshots shared during development were used to diagnose layout and configuration problems.
- [Author to confirm: no live passwords or API keys were pasted into AI conversations. Any key that was shared should be revoked and regenerated.]

## 8. Third-party code and assets

| Resource | Source | Note |
|---|---|---|
| PHPMailer | github.com/PHPMailer/PHPMailer | Library copied into `includes/phpmailer/`; licensed LGPL-2.1 |
| Baloo 2 typeface | Google Fonts | SIL Open Font Licence |
| Visual style | *Mac Magic* retro Webflow template (Flowmingo / Mackenzie Child) | Inspiration only; no template code copied |
| Map-pin illustration | original image gotten from bing search and then edited by the author using canva | Supplied by the author |

All other code was written by the author or generated with AI assistance as described above.

## 9. Understanding and responsibility

The author is responsible for the submitted work, including every line of AI-assisted code. During development the author requested explanations and step-by-step instructions in order to learn the underlying concepts, among them:

- prepared statements and SQL injection
- password hashing and token hashing
- CSRF protection and session security
- rate limiting and account-enumeration defences
- environment variables and secret management
- Git and GitHub workflow
- containerised deployment and the constraints of free hosting tiers

## 10. Compliance with institutional policy

This disclosure is made in accordance with [name of the institution's or course's academic integrity / AI use policy, and section number if applicable]. [State whether AI assistance was permitted for this assignment and under what conditions.]

## 11. Declaration

I confirm that the information in this statement is accurate, that I have reviewed, tested and understood the work I am submitting, and that I accept responsibility for it.

**Name:** Alfred Ayeba

**Signature:** ____________________________

**Date:** 07/11/2026

---

## Appendix A: Representative interactions

The following list paraphrases the kinds of requests made to the AI, in roughly chronological order. The complete conversation can be provided on request.

1. "I'm having issues in my PHP code" (shared login and dashboard files; asked to show only the first name).
2. "Design my dashboard to look similar to this template" (reference screenshot supplied).
3. Requests to restyle the login, register, listing and detail pages to match.
4. Layout adjustments directed by the author: smaller cards, larger images, square statistic cards, hidden window titles, logout button placement, removing a white gap at the page bottom.
5. "Add a returned card" and "add the claimed card" to the dashboard statistics.
6. "A page to view only the specific item clicked."
7. "Now the admin page."
8. "What is left on the roadmap?" followed by "follow your suggested order in intense detail and step by step."
9. Git and GitHub setup, with troubleshooting of remote and authentication errors.
10. CSRF protection and session hardening, with debugging of a missing-function error.
11. Profile page: spacing between cards, then a full review and rewrite.
12. "Add a CAPTCHA API and email verification," followed by password reset and a login rate limiter.
13. Deployment: choice of host, database, image storage and email services; Docker configuration; debugging of database connection errors on the author's machine.
14. Generation of this statement and the project README.

## Appendix B: Files that contain substantial AI-assisted code

All files in the repository should be regarded as AI-assisted unless the author states otherwise. The author's original work is concentrated in: the project concept, the initial database tables, the first versions of the registration, login and dashboard pages, the first version of the profile page, all design and layout decisions, configuration and operation of the cloud services, testing, and the final review.
