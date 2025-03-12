# WordPress Plugin Technical Assessment

## 📌 Overview
This project sets up a **WordPress environment with Docker** and includes a **Simple Reviews Plugin** for a technical assessment. 

Candidates will:
1. **Set up the local environment using Docker**.
2. **Debug and extend the `Simple Reviews` WordPress plugin**.

---

## 🚀 **Getting Started**

### **🔧 Prerequisites**
- **Docker** & **Docker Compose** installed.

---

## 🛠 **Setup Instructions**
### **Step 1: Clone the Repository**
```bash
git clone https://gitlab.com/search-atlas-interviews/wordpress-plugin-product-reviews
cd wordpress-plugin-product-reviews
```

### **Step 2: Start the WordPress Environment**
```bash
docker-compose -f docker/docker-compose.yml up --build -d
```
> This will start WordPress, MySQL, and phpMyAdmin.

### **Step 3: Access WordPress**
- **Admin Panel:** [http://localhost:8080/wp-admin](http://localhost:8080/wp-admin)
  - Username: `admin`
  - Password: `admin`
- **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)
  - Username: `root`
  - Password: `root`

---

## 📂 **Project Structure**
```
├── docker/
│   ├── Dockerfile  # Custom WordPress image
│   ├── docker-compose.yml  # Service definitions
│   ├── init.sql  # Initial database setup
│
├── scripts/
│   ├── post-init.sh  # WordPress setup script
│
├── wordpress/
│   ├── wp-content/plugins/simple-reviews/  # Plugin for assessment
│
├── README.md  # This file
```

---

## ✅ **Testing the Plugin**
After modifications, test via:
```bash
curl -X GET http://localhost:8080/wp-json/mock-api/v1/review-history
```

Verify shortcode display:
1. Go to **WordPress Admin**.
2. Create a new post.
3. Insert `[product_reviews]` and **preview**.

---

## 🛑 **Stopping and Cleaning Up**
```bash
docker-compose -f docker/docker-compose.yml down -v
```

---

## 🎯 **Final Notes**
- The repository will be shared **in advance**.
- The actual **assessment tasks will be provided separately** during the interview.
- Ensure that any new REST API endpoints **are publicly accessible**.

Good luck! 🚀
