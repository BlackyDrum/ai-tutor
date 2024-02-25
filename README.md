# Chatbot

---

## Installation
**Follow these steps to get the Chatbot up and running on your local machine:**
1. **Clone the repository:**
```
$ git clone https://github.com/BlackyDrum/chatbot.git
```
2. **Navigate to the project directory:**
```
$ cd chatbot
```
3. **Install the dependencies:**
```
$ composer install
```
4. **Create a copy of the .env.example file and rename it to .env. Update the necessary configuration values such as the Database- and API Credentials:**
```
$ cp .env.example .env
```
5. **Generate an application key:**
```
$ php artisan key:generate
```
6. **Install Nova:**
```
$ php artisan nova:install
```
7. **Run the database migrations:**
```
$ php artisan migrate
```
8. **Install JavaScript dependencies:**
```
$ npm install
```
9. **Build the assets:**
```
$ npm run dev
```
10. **Start the development server:**
```
$ php artisan serve
```
11. **Visit http://localhost:8000 in your web browser to access the application.**
