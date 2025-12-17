# 🚀 Quick Setup Guide - Influencer Collaborations

## ⚡ 5-Minute Setup

### Step 1: Create Database Table
Open phpMyAdmin or your MySQL client and run:

```sql
CREATE TABLE IF NOT EXISTS `influencer_collabs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collab_title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `product_description` text NOT NULL,
  `product_link` varchar(500) DEFAULT NULL,
  `photo_1` varchar(500) DEFAULT NULL,
  `photo_2` varchar(500) DEFAULT NULL,
  `photo_3` varchar(500) DEFAULT NULL,
  `financial_type` enum('barter','paid') DEFAULT 'barter',
  `financial_amount` decimal(10,2) DEFAULT 0.00,
  `detailed_summary` text NOT NULL,
  `brand_email` varchar(255) NOT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `accepted_by` int(11) DEFAULT NULL,
  `accepted_on` datetime DEFAULT NULL,
  `completed_on` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**OR** run the provided SQL file:
```bash
mysql -u root -p your_database < admin/database_influencer_collabs.sql
```

### Step 2: Configure Email (Optional)
Edit `admin/src/ui/collaborations.php` line ~230:

```php
$admin_email = "your-admin@email.com"; // Change this
```

### Step 3: Start Your Server
```bash
# Windows with XAMPP
# Just make sure Apache and MySQL are running

# Or navigate to
http://localhost/qr/admin/src/ui/collaborations.php
```

### Step 4: Access the Feature
1. Log in to your admin panel
2. Click **"Influencer Collabs"** in the sidebar
3. Start creating collaborations!

---

## 📋 First Collaboration Example

### Create Your First Collab:
1. Click **"Create New Collab"**
2. Fill in:
   - **Title**: "Test Skincare Campaign"
   - **Category**: Skincare
   - **Description**: "Promote our new moisturizer to your audience"
   - **Product Link**: https://example.com/product
   - **Upload**: 1-3 product images
   - **Financial**: Select "Barter" or "Paid" (enter ₹5000 if paid)
   - **Summary**: "Create 1 Instagram post and 3 stories featuring our product"
   - **Brand Email**: brand@example.com
3. Click **"Create Collaboration"**

---

## ✅ Checklist

- [ ] Database table created
- [ ] Files uploaded to server
- [ ] Apache & MySQL running
- [ ] Admin panel accessible
- [ ] Sidebar shows "Influencer Collabs" menu
- [ ] Page loads without errors
- [ ] Can create test collaboration
- [ ] Photos upload successfully
- [ ] Email notifications configured (optional)

---

## 🎨 Features Overview

### **3 Tabs with Unique Banners**

#### Tab 1: Pending Requests
- 3 Banners showcasing campaign creation flow
- View all pending collaboration requests
- Download product photos
- Delete unwanted requests

#### Tab 2: Active Collabs
- 3 Banners focusing on monitoring and relationships
- Track ongoing collaborations
- Mark as completed
- View influencer details

#### Tab 3: Completed
- 3 Banners emphasizing success and analytics
- Review finished campaigns
- Analyze performance
- Reference for future work

### **Collaboration Cards Include**
✓ 3 Product Photos (downloadable)  
✓ Product Description  
✓ Category Badge  
✓ Product Link  
✓ Financial Details (Barter/Paid)  
✓ Detailed Summary  
✓ Status Badge  
✓ Action Buttons  

---

## 🎯 Email Notifications

When an influencer accepts a collaboration, **3 emails** are automatically sent:

1. **To Brand** (brand_email)
   - Confirmation of acceptance
   - Influencer details
   - Collaboration summary

2. **To Influencer** (influencer's email)
   - Collaboration details
   - Product information
   - Next steps

3. **To Admin** (admin_email in code)
   - Record of new active collab
   - All party details
   - Financial information

---

## 🔧 Troubleshooting

### Issue: Page shows blank
- Check PHP error logs
- Verify database connection in `admin/src/backend/dbconfig/connection.php`
- Ensure all files are in correct locations

### Issue: Photos won't upload
- Create directory: `mkdir uploads/collabs`
- Set permissions: `chmod 777 uploads/collabs`
- Check PHP `upload_max_filesize` (default 2MB)

### Issue: No data showing
- Verify database table exists
- Check database connection
- Open browser console for JavaScript errors

### Issue: Emails not sending
- Update `$admin_email` in code
- Configure SMTP for production
- Check spam folders

---

## 📱 Mobile Responsive

The interface automatically adapts to:
- Desktop (full grid layout)
- Tablet (2-column layout)
- Mobile (single column, touch-friendly)

---

## 🎨 Customization

### Change Colors
Edit the CSS gradient values in `collaborations.php`:
```css
/* Current: Pink to Yellow */
background: linear-gradient(135deg, #E9437A, #E2AD2A);

/* Example: Blue to Purple */
background: linear-gradient(135deg, #3B82F6, #A855F7);
```

### Add Categories
Edit the dropdown in the modal (~line 816):
```html
<option value="beauty">Beauty</option>
<option value="wellness">Wellness</option>
```

---

## 🚀 Next Steps

1. **Test the feature** with sample data
2. **Customize email templates** for your brand
3. **Add URL permissions** if using role-based access
4. **Integrate with influencer dashboard** (future enhancement)
5. **Track collaboration metrics** (future enhancement)

---

## 📞 Support

If you encounter issues:
1. Check the main `README_COLLABORATIONS.md` for detailed docs
2. Review browser console for errors
3. Check PHP error logs
4. Verify database structure matches schema

---

**Ready to launch influencer campaigns! 🎉**
