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
4. **Create a copy of the .env.example file and rename it to .env. Update the necessary configuration values such as the Database- and API Credentials:**
```
$ cp .env.example .env
```
5. **Verify your Nova License Key:**
```
$ php artisan nova:check-license
```
6. **Generate an application key:**
```
$ php artisan key:generate
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
$ npm run build
```
10. **Run the ChromaDB Docker container:**
```
$ docker-compose up -d
```
11. **(Optional) Synchronize ChromaDB with the relational database:**
```
$ php artisan chroma:sync
```
12. **(Optional) Seed the database with demo data:**
```
$ php artisan db:seed --class=DemoSeeder
```
13. **Start the development server:**
```
$ php artisan serve
```
14. **Visit http://localhost:8000 in your web browser to access the application.**

## Dashboard Access
In order to have full access to the Admin's Dasboard, you need to manually set the ``admin`` column in the ``users`` table to true. You can use the following command:
```sql
UPDATE users SET admin=true WHERE id={USER_ID};
```

## Validate ChromaDB Sync
To validate if ``ChromaDB`` is in sync with the relational database, you can use the following command:
```
$ php artisan chroma:check
```

## Sync ChromaDB with relational database
If the validation failed, or if you want to synchronize ChromaDB with the relational database, you can use the following command:
```
$ php artisan chroma:sync
```
This step is beneficial for scenarios where ChromaDB has retained data that is yet to be reflected in the relational database.

## Clearing ChromaDB Data
To completely remove all stored data related to ``ChromaDB``, including ``embeddings`` and ``collections``, you can use the following command:
```
$ php artisan chroma:destroy
```

