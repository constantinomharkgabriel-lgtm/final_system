# System Architecture Flowchart (Full System, Complete)

Below is the updated architecture flowchart for your poultry management system, now explicitly including Super Admin, Farm Owner, Employee, Consumer (web/mobile), and all major modules:

```mermaid
graph TD
    SA[Super Admin] -->|Admin Actions| SADash[Super Admin Dashboard]
    SADash -->|System Management| API[Laravel Controllers]
    FO[Farm Owner] -->|Farm Management| FODash[Farm Owner Dashboard]
    FODash -->|Requests| API
    Emp[Employee] -->|Attendance/Tasks| EmpUI[Employee Web UI]
    EmpUI -->|Requests| API
    WebCons[Web Consumer] -->|Browse/Order| WebMarket[Web Marketplace]
    MobCons[Mobile Consumer] -->|Browse/Order| MobMarket[Mobile Marketplace]
    WebMarket -->|API Calls| API
    MobMarket -->|API Calls| API
    API -->|Processes| Models[Models]
    Models -->|Data| DB[(MySQL Database)]
    API -->|Auth| Auth[Authentication]
    API -->|Reports| Reports[Reporting]
    API -->|Notify| Notif[Notifications]
    subgraph Backend
        API
        Models
        DB
        Auth
        Reports
        Notif
    end
    Reports -->|To| SADash
    Reports -->|To| FODash
    Reports -->|To| EmpUI
    Notif -->|To| SADash
    Notif -->|To| FODash
    Notif -->|To| EmpUI
    Notif -->|To| WebMarket
    Notif -->|To| MobMarket
```

---

> To generate the image, copy the Mermaid code above into [Mermaid Live Editor](https://mermaid.live/) and export as PNG or SVG.
