# EV Mobile Power & Service Station

A smart support system for stranded electric vehicles, providing on-demand charging and mechanical support through a digital platform.

## 🚗 Project Overview

This project addresses the critical need for emergency support when electric vehicles run out of battery in remote areas or on highways. Our solution provides:

- **On-demand EV charging** - Mobile charging units with portable fast chargers
- **Emergency mechanical support** - Professional technicians for repairs and diagnostics
- **Digital platform** - Web-based service for requesting help and tracking

## ✨ Features

### User Module
- User registration and login system
- Request for EV charging services
- Request for mechanical support
- Real-time location sharing
- Track service vehicle arrival
- Payment integration
- Service history tracking

### Admin Module
- Manage user accounts
- Assign service vehicles
- Monitor service requests
- Track vehicle locations
- Manage technicians
- Generate reports and analytics

## 🛠️ Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Hosting**: Cloud-based (AWS or similar)
- **Maps & Location**: Google Maps API Integration

## 📁 Project Structure

```
ev/
├── index.php              # Main landing page
├── login.php              # User login page
├── signup.php             # User registration page
├── dashboard.php          # User dashboard
├── logout.php             # Logout functionality
├── database.sql           # MySQL database schema
└── README.md              # Project documentation
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone or download the project files**
   ```bash
   # Place all files in your web server directory
   ```

2. **Set up the database**
   ```bash
   # Import the database schema
   mysql -u your_username -p your_database < database.sql
   ```

3. **Configure database connection**
   - Update database credentials in your PHP files (when implementing backend)
   - Default database name: `ev_mobile_station`

4. **Start your web server**
   ```bash
   # For Apache
   sudo service apache2 start
   
   # For XAMPP/WAMP
   # Start Apache and MySQL services
   ```

5. **Access the application**
   ```
   http://localhost/ev/
   ```

## 🔐 Demo Credentials

### User Login
- **Username**: `demo`
- **Password**: `demo123`

### Admin Login
- **Username**: `admin`
- **Password**: `admin123`

## 📊 Database Schema

The database includes the following tables:

- **users** - User account information
- **service_vehicles** - Mobile service vehicles
- **technicians** - Service technicians
- **service_requests** - Service requests from users
- **payments** - Payment transactions
- **service_history** - Service request history
- **admin_users** - Admin account management

## 🎨 Features Implemented

### ✅ Completed Features
- [x] Modern responsive landing page
- [x] User registration and login system
- [x] User dashboard with statistics
- [x] Service request tracking
- [x] Database schema design
- [x] Mobile-friendly design
- [x] Interactive UI elements

### 🔄 Future Enhancements
- [ ] Real backend integration with database
- [ ] Google Maps API integration
- [ ] Real-time tracking system
- [ ] Payment gateway integration
- [ ] Admin dashboard
- [ ] Mobile app development
- [ ] Push notifications
- [ ] SMS/Email notifications

## 🎯 How It Works

1. **User requests help** - Through website/app with location and issue description
2. **System matches** - Automatically finds nearest available service vehicle
3. **Track arrival** - Real-time tracking of service vehicle
4. **Receive service** - Professional charging or mechanical support
5. **Payment** - Secure payment processing

## 📱 User Interface

The application features a modern, responsive design with:
- Clean and intuitive navigation
- Mobile-first responsive design
- Interactive elements and animations
- Professional color scheme
- Font Awesome icons for better UX

## 🔧 Customization

### Styling
- All styles are included inline for easy customization
- Color scheme can be modified in CSS variables
- Responsive breakpoints for different screen sizes

### Functionality
- Dummy data is used for demonstration
- Easy to integrate with real backend services
- Modular code structure for easy extension

## 📞 Support

For support or questions about this project:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## 📄 License

This project is created for educational and demonstration purposes.

## 🙏 Acknowledgments

- Font Awesome for icons
- Unsplash for background images
- Modern web development practices and standards

---

**Note**: This is a demonstration project with dummy data. For production use, implement proper backend integration, security measures, and real database connections. 