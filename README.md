<div style="background-color: red; color: black;">
<h3>THIS PROJECT IS CURRENTLY IN DEVELOPMENT DON'T USE THIS</h3>
<p>MATTER OF FACT DON'T USE ANYTHING I MAKE ANYWAY THEY ALL SUCK</p>
</div>

![leragonlogo](https://raw.githubusercontent.com/lera2od/Leragon/refs/heads/main/leragonlogo.png)

# leragon
most peak docker manager built to suit my needs.

this docker manager assumes all docker containers starting with the same name are part of one big project.

## backstory
This project exists because I was used to [Laragon](https://laragon.org) php manager idk something made by someone go research it if you want to find out. That project was so peak the moment i switched to [Linux](https://en.wikipedia.org/wiki/Linux) I was devastated to find out it didnt exist for linux and I had to use docker (I didn't have to use docker but since I had some experience with it I decided on that). The only good docker manager I ever used was [Portainer](https://www.portainer.io) and it is paid and [Yacht](https://en.wikipedia.org/wiki/Feces) fucking sucks

## progress
<div style="height: 24px; width: 100%; background-color: white; border-radius: 12px; overflow: hidden;">
<div style="height: 24px; width: 5%; background-color: green;">%5</div>
</div>
<br>
This project is far from finished or even usable. I will do a little todo list below:

| ✓ | Task |
|---|------|
| ✓ | global -> projects |
| ✓ | projects -> containers |
| ✓ | projects -> images |
| ✓ | projects -> networks |
| ✓ | projects -> volumes |
| ✓ | projects -> logs |
| ✓ | Light-Dark themes |
| ✓ | Installation Script |
| ✓ | Auth (really basic auth) |
| ☐ | projects -> settings |
| ☐ | images |
| ☐ | networks |
| ☐ | volumes |
| ☐ | logs |
| ☐ | settings |
| ☐ | advanced auth |

## usage
**AS I SAID DONT USE THIS** 

but if you want to

First make yourself a better database username and password and set it in `docker-compose.yaml` and `app/config/mysql.ini`. If you skipped this step and want to go back just delete the database volume.

Second make sure you have Docker and Docker-compose installed.

Third run `sudo ./setup.sh`

Fourth open `http://localhost:3001` or any port you specified and create the first account