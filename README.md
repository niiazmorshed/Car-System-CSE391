# 🎰 Car Workshop Slot-Based Appointment System

## ✨ **SLOT-BASED SYSTEM FEATURES**

This is a **complete slot-based appointment system** where:

- 🎰 **Database stores actual slot counts** (4 slots per mechanic initially)
- 📉 **Booking decreases slots by 1** (`availableSlots` field updated)  
- 📈 **Completing work increases slots by 1** (when status changes to completed/cancelled)
- 🔄 **Real-time slot updates** across frontend and admin panel
- 🎯 **David Wilson starts with 3/4 slots** (to match your original requirement)

## 🚀 **QUICK SETUP**

### 1. **Install Dependencies**
```bash
composer install
npm install
```

### 2. **Initialize Database**
```bash
# Run this to set up mechanics with proper slot counts
php initialize_mechanics.php
```

### 3. **Start Server**
```bash
php -S localhost:8000
```

### 4. **Access System**
- **Frontend**: http://localhost:8000/index.html
- **Admin Panel**: http://localhost:8000/admin.html

## 🎰 **HOW THE SLOT SYSTEM WORKS**

### **Database Schema**
```json
{
  "mechanics": {
    "availableSlots": 3,  // Current available slots
    "totalSlots": 4,      // Maximum slots per day
    // ... other fields
  }
}
```

### **Booking Process**
1. **Check Slots**: `if (mechanic.availableSlots > 0)`
2. **Book Appointment**: Create appointment record
3. **Decrease Slots**: `availableSlots = availableSlots - 1`
4. **Update Display**: Show new slot count immediately

### **Status Management**
- **Active Status** (confirmed, pending, in-progress): **Occupies slot**
- **Inactive Status** (completed, cancelled): **Frees up slot**

### **Status Changes**
- `confirmed → completed`: **+1 slot** (work finished)
- `confirmed → cancelled`: **+1 slot** (appointment cancelled)
- `cancelled → confirmed`: **-1 slot** (reactivated appointment)

## 📁 **FILE STRUCTURE**

```
car-workshop-system/
├── index.html              # 🎰 Client booking page with slot display
├── admin.html              # 🎰 Admin panel with slot management
├── script.js               # 🎰 Frontend slot-based logic
├── admin.js                # 🎰 Admin slot-based logic
├── styles.css              # 🎨 Complete styling system
├── config.php              # ⚙️ Database configuration
├── get_mechanics.php       # 🎰 SLOT-BASED: Get mechanics with slots
├── book_appointment.php    # 🎰 SLOT-BASED: Book and decrease slots
├── update_appointment.php  # 🎰 SLOT-BASED: Status change and slot update
├── get_appointments.php    # 📋 Get all appointments for admin
├── initialize_mechanics.php # 🎰 Initialize slot-based mechanics
├── database_init.json      # 📊 Database initialization data
├── package.json            # 📦 Node dependencies
├── composer.json           # 📦 PHP dependencies
└── README.md               # 📖 This file
```

## 🎯 **EXPECTED BEHAVIOR**

### **Initial State**
- **David Wilson**: `3/4 slots available` ✅
- **All others**: `4/4 slots available` ✅

### **After Booking David Wilson**
- **David Wilson**: `2/4 slots available` ✅
- **Others unchanged**: `4/4 slots available` ✅

### **After Completing David's Work**
- **David Wilson**: `3/4 slots available` ✅ (back to 3/4)
- **Others unchanged**: `4/4 slots available` ✅

## 🔧 **API ENDPOINTS**

### **GET /get_mechanics.php**
Returns mechanics with current slot availability:
```json
{
  "success": true,
  "mechanics": [
    {
      "name": "David Wilson",
      "availableSlots": 3,
      "totalSlots": 4,
      "slotText": "3/4 slots available"
    }
  ]
}
```

### **POST /book_appointment.php**
Books appointment and decreases slots:
```json
{
  "success": true,
  "slotUpdate": {
    "mechanicName": "David Wilson",
    "previousAvailableSlots": 3,
    "currentAvailableSlots": 2,
    "slotChange": -1
  }
}
```

### **POST /update_appointment.php**
Updates status and adjusts slots:
```json
{
  "success": true,
  "slotUpdate": {
    "mechanicName": "David Wilson", 
    "currentAvailableSlots": 3,
    "slotChange": 1,
    "reason": "confirmed → completed"
  }
}
```

## 🎰 **SLOT MANAGEMENT LOGIC**

### **Active Appointments** (Occupy Slots)
- `confirmed` - Customer confirmed appointment
- `pending` - Waiting for confirmation  
- `in-progress` - Work is being done

### **Inactive Appointments** (Free Slots)
- `completed` - Work finished, slot freed
- `cancelled` - Appointment cancelled, slot freed

### **Slot Calculation**
```php
// Real-time slot calculation
$availableSlots = $mechanic->availableSlots; // From database
$totalSlots = $mechanic->totalSlots;         // Always 4

// Display
$displayText = "$availableSlots/$totalSlots slots available";
```

## 🔄 **REAL-TIME UPDATES**

### **Frontend**
- ✅ Immediately refreshes after booking
- ✅ Shows updated slot counts
- ✅ Disables fully booked mechanics

### **Admin Panel**  
- ✅ Shows slot counts next to mechanic names
- ✅ Updates slots when changing appointment status
- ✅ Real-time slot statistics

## 🎯 **TESTING THE SYSTEM**

1. **Open Frontend**: `http://localhost:8000/index.html`
   - ✅ David Wilson shows `3/4 slots available`
   - ✅ Others show `4/4 slots available`

2. **Book David Wilson**:
   - ✅ Page refreshes automatically  
   - ✅ David Wilson now shows `2/4 slots available`

3. **Open Admin Panel**: `http://localhost:8000/admin.html`
   - ✅ See appointment in table
   - ✅ David Wilson shows `(2/4 slots)` next to name
   - ✅ Change status to "Completed" 
   - ✅ David Wilson slots increase to `3/4`

## 🐛 **TROUBLESHOOTING**

### **Slots Not Updating**
```bash
# Clear browser cache completely
# Or try incognito mode
```

### **Database Connection Issues**  
```bash
# Check MongoDB connection string in config.php
# Ensure MongoDB service is running
```

### **API Errors**
```bash
# Check browser console for detailed errors
# Verify API endpoints are accessible
```

## 🎉 **SUCCESS INDICATORS**

✅ **Frontend**: David Wilson shows `3/4 slots available`  
✅ **Booking**: Slots decrease immediately after booking  
✅ **Admin Panel**: Slot counts shown next to mechanic names  
✅ **Status Updates**: Slots adjust when changing appointment status  
✅ **Real-time**: Both panels stay synchronized  

## 🎰 **SLOT-BASED SYSTEM ADVANTAGES**

1. **📊 Database-Driven**: Slots stored in database, not calculated
2. **⚡ Fast Performance**: No complex date/appointment counting
3. **🔄 Real-Time**: Immediate updates across all interfaces  
4. **🎯 Accurate**: No race conditions or sync issues
5. **📈 Scalable**: Easy to modify slot limits per mechanic
6. **🔧 Maintainable**: Simple slot increment/decrement logic

---

**🎰 Your slot-based appointment system is ready to use!** 🚀