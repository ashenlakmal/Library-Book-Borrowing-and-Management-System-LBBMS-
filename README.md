#  Smart Library Book Borrowing and Management System

##  Project Overview
This is a web-based library management system developed for university students and staff.  
The system allows users to borrow, return, and reserve books online while helping library staff manage records efficiently.

The main goal of this project is to reduce manual work, improve accuracy, and make library services faster and easier.

---

##  Features

###  Student Features
- Register and login securely
- View available books
- Borrow books (maximum 2 books at a time)
- Return books
- Reserve unavailable books
- View borrowing history
- Check fines for late returns

###  Admin (Staff) Features
- Admin login
- Add, edit, and delete books
- Manage book availability
- View student borrowing records
- Mark books as returned
- Manage reservations

---

##  Technologies Used

- **Frontend:** HTML, CSS, JavaScript  
- **Backend:** PHP  
- **Database:** MySQL  
- **Frameworks/Libraries:** Bootstrap, Tailwind CSS, jQuery  
- **Server:** XAMPP  

---

## 📁 Project Structure

```
📦 Smart Library System
├── 📁 about us            # About us pages
├── 📁 books               # Book-related features and pages
├── 📁 bootstrap and jQuery # CSS & JS libraries
├── 📁 images              # Images used in the system
├── 📁 other               # Additional files
├── 📁 staff               # Admin/staff functionalities
├── 📁 student             # Student functionalities
├── 📁 uploads             # Uploaded files (images, etc.)

├── 📄 index.html          # Home page
├── 📄 barrowbook.html     # Borrow book page
├── 📄 contactus.html      # Contact page

├── 📄 README.md           # Project documentation
```
## 🎥 Demo Video

Watch the full system demonstration:

[![Watch the demo](https://img.youtube.com/vi/eSolxwhkL20/0.jpg)](https://youtu.be/eSolxwhkL20)

##  System Functionalities

---

- Borrowing limit: Maximum 2 books per student  
- Borrow duration: 2 weeks  
- Automatic fine calculation for late returns  
- Real-time book availability checking  
- Book reservation system  

---

##  Database Design

Main tables used in the system:

- `student`
- `staff`
- `book`
- `book_record`
- `reserve_record`
- `student_phone`
- `staff_phone`

The system maintains relationships between students, books, and borrowing records to ensure accurate tracking.

---

##  Non-Functional Features

- User-friendly interface  
- Secure login system  
- Fast performance   
---

##  Testing

The system was tested using:

- Unit Testing  
- Integration Testing  
- System Testing  
- User Acceptance Testing (UAT)  

All core features were tested successfully without critical errors.

---

##  Performance

- Fast response time for all operations  
- Accurate data updates (borrowing, returning, reservations)  
- Efficient database queries  
- Stable under normal usage conditions  

---

##  Project Objectives

- Develop a simple and user-friendly library system  
- Automate borrowing and returning processes  
- Reduce manual errors  
- Improve efficiency of library operations  
- Provide better experience for students and staff  

---

##  Future Improvements

- Email/SMS notification system  
- Mobile application (Android/iOS)  
- Multi-language support (Sinhala, Tamil, English)  
- AI-based book recommendations  
- Online payment for fines  
- Cloud deployment  

---

##  Team Members

- E. A. Didula Pabasara  
- K. A. A. Lakmal  
- W. N. Buddini  
- M. A. D. Tharindi Navodi  
- M. S. Madusha Priyashan  

---

##  University

Uva Wellassa University of Sri Lanka  
Department of Information and Communication Technology  

---

## License
This project is developed for academic purposes.

---

## ⚙️ Installation

### 1. Clone Repository

```bash
https://github.com/ashenlakmal/Library-Book-Borrowing-and-Management-System-LBBMS-.git
```

### 2. Database Setup

* Start **Apache** and **MySQL** in XAMPP
* Create database: `lfms`
* Import SQL file from `/database`

> ⚠️ Add admin manually to `admins` table first time.

---

### 3. Run Project

* Move project to `htdocs`
* Open browser:

```
http://localhost/Library-Book-Borrowing-and-Management-System-LBBMS-
```

##  Acknowledgement

Special thanks to our supervisors for their guidance and support throughout this project.

---
