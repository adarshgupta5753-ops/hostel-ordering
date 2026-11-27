HOSTEL GROUP ORDERS - UPDATED VERSION

Naye features:
- Place Order ke andar Automatic aur Manual options.
- Hostel dropdown: Tagore / VS.
- Shopping app dropdown: Blinkit / Zepto.
- Automatic groups hostel + app ke hisaab se alag.
- Manual listings mein name, roll, room, threshold, purchase amount aur patience expiry.
- Manual users apni listing banakar kisi compatible listing/group ko Join kar sakte hain.
- Group name Group-<database ID> hai; round number user interface mein nahi hai.
- Threshold minimum personal threshold hai; purchase amounts add hote hain.
- Group complete hone par members ko one-time popup notification.
- Group history aur failed manual listings history.
- Mobile number group complete hone ke baad hi group members ko dikhta hai.

INSTALL / UPDATE:
1. XAMPP mein Apache aur MySQL start karein.
2. Apne existing folder C:\xampp\htdocs\hostel-ordering ka backup lein.
3. ZIP ke hostel-ordering folder ke andar ki files apne existing folder mein copy/replace karein.
4. Browser mein http://localhost/hostel-ordering/install.php ek baar open karein.
   Ye naye tables/columns create karega aur existing users ko preserve karega.
5. Install complete hone ke baad install.php ko delete ya rename karein.
6. Website open karein: http://localhost/hostel-ordering/

HOSTING PAR:
- Pehle hosting panel mein MySQL database banayein aur local database export/import karein.
- config.php mein hosting ke DB_HOST, DB_NAME, DB_USER, DB_PASS set karein.
- Files hosting ke htdocs folder mein upload karein.
- install.php ek baar run karein, phir server se delete karein.

IMPORTANT NOTES:
- Manual patience expiry har page request par check hoti hai. Manual listing page open rehne par 30-second refresh bhi expiry check karta hai. Agar user site band kar de aur free host cron jobs support na kare, expiry processing agle page visit par hogi; exact second par background cleanup guaranteed nahi hai.
- Group serial number database ka auto-increment ID hai. Purane database ke groups ke IDs bhi count mein rahenge.
- Admin default login: admin@hostel.local / Admin@12345. First login ke baad password change karein.
- Free hosting ko real students ke mobile data ke saath use karne se pehle HTTPS, access control, backups aur hosting provider ki privacy policy verify karein.
