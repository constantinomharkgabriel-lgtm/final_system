# Data Flow Diagram (DFD) - Poultry Management System (Complete)

Below is a Level 1 Data Flow Diagram (DFD) for your system, now explicitly including Super Admin, Farm Owner, Employee, and Consumer (web/mobile) roles, as well as all major modules and flows.

```mermaid
graph TD
    SuperAdmin[Super Admin] -->|Manage Users, Farms, System| AdminUI[Super Admin Dashboard]
    AdminUI -->|Admin Actions| API[Laravel Controllers]
    FarmOwner[Farm Owner] -->|Login/Register| FO_UI[Farm Owner Web UI]
    FO_UI -->|Farm Data, Requests| API
    Employee[Employee] -->|Login/Attendance/Tasks| Emp_UI[Employee Web UI]
    Emp_UI -->|Attendance, Task Data| API
    Consumer[Web Consumer] -->|Browse/Order| WebMarket[Web Marketplace]
    ConsumerMobile[Mobile Consumer] -->|Browse/Order| MobMarket[Mobile Marketplace]
    WebMarket -->|Order/Product Data| API
    MobMarket -->|Order/Product Data| API
    API -->|CRUD Operations| DB[(MySQL Database)]
    API -->|Authentication| Auth[Auth System]
    API -->|Business Logic| Logic[Business Logic]
    API -->|Generate| Reports[Reports]
    API -->|Send/Receive| Notif[Notifications]
    Logic -->|Update| DB
    DB -->|Data| API
    Reports -->|View| FO_UI
    Reports -->|View| AdminUI
    Notif -->|To User| FO_UI
    Notif -->|To User| WebMarket
    Notif -->|To User| MobMarket
    Notif -->|To User| Emp_UI
    Notif -->|To User| AdminUI
```

---

> To generate the image, copy the Mermaid code above into [Mermaid Live Editor](https://mermaid.live/) and export as PNG or SVG.
