## feature-haproxyToAp-sftp-db

- SFTP-Prod：對外開 `22` Prot。
- SFTP-Uat：對外開 `8022` Prot。(內部走 `22` Prot)
- HAProxy：對外開 `80` Port 分流 → AP1、AP2。
- DB：僅內部網路可以存取。

---
