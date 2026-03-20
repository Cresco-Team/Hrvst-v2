# Centralized Digital System of La Trinidad Vegetable Trading Post
---
### Problem
 This project aims to solve the problem of:
 * Farmers not able to sell all their vegetables due to lack of connections with dealers leading to cheap offers.
 * Dealers not able to get the best deal before going to Trading Post leading to unbalanced prices.
---
### Features
 * Product posting of farmers to let the dealers know what's available.
 * Product posting of vegetable the dealers need to notify the farmers.
 * Transparent vegetable pricing with data analytics (Price history graph).
#### The whole point of this system is to provide Users with the best planning and decision they could take before going to trading post to avoid any potential waste of time and money.
---
### Users
 * Admin (Trading Post Manager)
 * Farmer
 * Dealer
---
### Tech Stack
![Static Badge](https://img.shields.io/badge/Laravel-%23F54927?style=for-the-badge&logo=laravel&logoColor=%23F54927&label=PHP&labelColor=black)
![Static Badge](https://img.shields.io/badge/Vue-%2323CC78?style=for-the-badge&logo=vuedotjs&logoColor=%2323CC78&label=TS&labelColor=black)
![Static Badge](https://img.shields.io/badge/SQLite-%2325A2DB?style=for-the-badge&logo=sqlite&logoColor=%2325A2DB&label=SQL&labelColor=black)
![Static Badge](https://img.shields.io/badge/Inertia-%237B29D6?style=for-the-badge&logo=inertia&logoColor=%237B29D6&label=JS&labelColor=black)
![Static Badge](https://img.shields.io/badge/Leaflet-%235ED12C?style=for-the-badge&logo=leaflet&logoColor=%235ED12C&label=JS&labelColor=black)
![Static Badge](https://img.shields.io/badge/ShadCN-%23D4D4D4?style=for-the-badge&logo=shadcnui&logoColor=%23D4D4D4&label=UI&labelColor=black)
![Static Badge](https://img.shields.io/badge/Tanstack-%2355E02F?style=for-the-badge&logo=tanstack&logoColor=%2355E02F&label=JS&labelColor=black)
![Static Badge](https://img.shields.io/badge/Chart-%23FF5E88?style=for-the-badge&logo=chartdotjs&logoColor=%23FF5E88&label=JS&labelColor=black)
![Static Badge](https://img.shields.io/badge/Postman-%23FF8145?style=for-the-badge&logo=postman&logoColor=%23FF8145&labelColor=black)
![Static Badge](https://img.shields.io/badge/Claude-%23FF8952?style=for-the-badge&logo=claude&logoColor=%23FF8952&label=AI&labelColor=black)
![Static Badge](https://img.shields.io/badge/Gemini-%238652FF?style=for-the-badge&logo=googlegemini&logoColor=%238652FF&label=AI&labelColor=black)

---
### Steps to Run
 * Run `git clone https://github.com/Cresco-Team/Hrvst-v2.git`
 * Run `cd Hrvst-v2`
 * Run `composer install` and `npm install`
 * Run `cp .env.example mv .env`
 * Run `php artisan key:generate`
 * Run `php storage:link`
 * Run `php artisan migrate --seed`
 * Run `composer run dev` if you're not using Herd
 * Create a new terminal and run `npm run dev`
