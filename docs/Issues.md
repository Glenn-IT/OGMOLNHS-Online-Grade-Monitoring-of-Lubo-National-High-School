# Issues & Resolutions

## Teacher Side
- **No duplicate of account check that**
  - **Status:** Done
  - **Resolution Details:**
    - Added duplicate check endpoint (`action=check_duplicate`) in `api/teachers.php`.
    - Enforced duplicate account checks in `send_signup_otp` and `register` in `api/teachers.php`.
    - Added real-time debounce checking on `views/teacher/signup.php` for:
      - **Email:** Checks if email is already active or pending approval.
      - **Teacher Full Name:** Prevents registering duplicate teacher accounts with identical names under different emails.
      - **Mobile Phone:** Checks if mobile number is already linked to an existing account.

## Admin Side
- **http://localhost/OGMS-Lubo-National-High-School/views/admin/manage-teachers.php**
- **On adding and editing a teacher the Fname, Lname, and Mname should have diff textboxes; check also on the registration for teachers**
  - **Status:** Done
  - **Resolution Details:**
    - In `views/admin/manage-teachers.php`:
      - Replaced single `Full Name` textbox with separate textboxes for **First Name** (`tFirstName`), **Middle Name** (`tMiddleName`, optional), and **Last Name** (`tLastName`).
      - On adding: Clears all three name textboxes.
      - On editing: Automatically populates First Name, Middle Name, and Last Name.
      - On saving: Validates required first and last names, constructs full name, and transmits separate name fields and full name to `api/teachers.php`.
    - In `views/teacher/signup.php` (Teacher Registration):
      - Added separate **Middle Name** (`tMiddle`, optional) textbox between First Name (`tFirst`) and Last Name (`tLast`).
      - Updated OTP verification, registration submission, and real-time duplicate checks to support Middle Name.

## Student Side
- **Filter by Subject on the student on the My Grades tab shows all subjects including Senior High**
  - **Status:** Done
  - **Resolution Details:**
    - In `api/grades.php`: Updated `action=list` to detect the student's enrolled grade level (`JHS` for Grades 7–10, `SHS` for Grades 11–12). Now returns only subjects corresponding to the student's curriculum level.
    - In `views/student/grades.php`: Populated `<select id="filterSubject">` with the student's level-appropriate subjects.
    - Fixed `views/student/dashboard.php` so the "Subjects Enrolled" stat reflects the student's actual level subjects (8 for JHS, 10 for SHS) rather than the global total of 27 subjects.
- **Subject accordion dropdown for terms 1–3 and responsive layout**
  - **Status:** Done
  - **Resolution Details:**
    - Replaced the flat table on `views/student/grades.php` with an interactive subject accordion. Clicking any subject (e.g. AP) drops down Terms 1–3 with DepEd component scores (Written Works 20%, Performance Tasks 50%, Quarterly Exam 30%), Term Grade, Remarks, and Proficiency level.
    - Added an overall subject average footer with DepEd descriptors.
    - Made the My Grades portal fully responsive for both desktop and mobile viewports:
      - 4-metric overview cards (Overall GPA, Passed Subjects, Enrolled Subjects, School Year).
      - Dedicated toolbar for Term and Subject filtering, plus Expand/Collapse All and Reset Filter buttons.
      - Dual-mode layout: Spacious table (`min-width: 720px`) on desktop ($\ge 768px$) and dedicated mobile term cards on smartphones ($< 768px$) to prevent compression and horizontal distortion.

