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
3. **Install the dependencies. You will be asked to provide a valid Nova Username and License Key. More information [here](https://nova.laravel.com/docs/installation.html):**
```
$ composer install
```
4. **Verify your Nova License Key:**
```
$ php artisan nova:check-license
```
5. **Create a copy of the .env.example file and rename it to .env. Update the necessary configuration values such as the Database- and API Credentials:**
```
$ cp .env.example .env
```
6. **Generate an application key:**
```
$ php artisan key:generate
```
7. **Run the database migrations:**
```
$ php artisan migrate
```
8. **(Optional) Create a user:**
```
$ php artisan nova:user
```
9. **Install JavaScript dependencies:**
```
$ npm install
```
10. **Build the assets:**
```
$ npm run build
```
11. **Run the ChromaDB Docker container:**
```
$ docker-compose up -d
```
12. **Start the development server:**
```
$ php artisan serve
```
13. **Visit http://localhost:8000 in your web browser to access the application.**

## Dashboard Access
In order to have full access to the Admin's Dasboard, you need to manually set the ``admin`` flag to true. You can use the following command:
```sql
UPDATE users SET admin=true WHERE id={USER_ID};
```

## Clearing ChromaDB Data
To completely remove all stored data related to ``ChromaDB``, including ``files``, ``embeddings``, and ``collections``, you can use the following command. Ensure that ``ChromaDB`` is running:
```
$ php artisan app:delete-collections
```

