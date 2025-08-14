# Laravel Blog API

A modern, feature-rich RESTful Blog API built with Laravel 12, featuring real-time broadcasting, comprehensive authentication, and advanced search capabilities.

## 🚀 Features

- **RESTful API Architecture** - Clean, standardized endpoints
- **Authentication & Authorization** - Laravel Sanctum token-based authentication
- **Real-time Broadcasting** - Laravel Reverb for live comments and likes
- **Rate Limiting** - API throttling for security and performance
- **Pagination** - Efficient data loading with customizable page sizes
- **Event & Listeners** - Decoupled event-driven architecture
- **Cache & Locale** - Multi-language support with local caching
- **Advanced Search & Filtering** - Full-text search and dynamic filters
- **Comprehensive Testing** - PHPUnit test coverage
- **Clean Code Architecture** - Following Laravel best practices

## 📋 Core Entities

- **Posts** - Create, read, update, delete blog posts
- **Comments** - Nested commenting system with real-time updates
- **Likes** - Like/unlike posts and comments with live notifications
- **Tags** - Categorization and filtering system

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **Broadcasting**: Laravel Reverb
- **Cache**: Local file cache
- **Testing**: PHPUnit

## ⚙️ Setup Steps

1. **Clone the repository**
```bash
https://github.com/MahmoudSalah53/blog-api.git
cd blog-api
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog-api
DB_USERNAME=root
DB_PASSWORD=
```

5. **Configure Broadcasting**
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

6. **Install broadcasting package (if not installed yet)**
```bash
php artisan broadcast:install
```

7. **Run migrations and seeders**
```bash
php artisan migrate --seed
```

8. **Start the services**
```bash
# Start Laravel development server
php artisan serve

# Start Laravel Reverb server (in another terminal)
php artisan reverb:start

# Start queue worker (in another terminal)
php artisan queue:work

# To run tests
php artisan test
```

## 📚 API Endpoints

### Authentication
| Method | Endpoint         | Description                | Auth Required |
|--------|------------------|----------------------------|---------------|
| POST   | `/api/register`  | User registration          | No            |
| POST   | `/api/login`     | User login                 | No            |
| POST   | `/api/logout`    | User logout                | Yes           |
| GET    | `/api/user`      | Get authenticated user     | Yes           |

### Posts
| Method | Endpoint                | Description                      | Auth Required |
|--------|-------------------------|----------------------------------|---------------|
| GET    | `/api/posts/all`        | Get all posts (with pagination)  | No            |
| GET    | `/api/posts/show/{id}`  | Get a specific post              | No            |
| POST   | `/api/posts/add`        | Create new post                  | Yes           |
| POST   | `/api/posts/edit/{id}`  | Edit post                        | Yes           |
| DELETE | `/api/posts/delete/{id}`| Delete post                      | Yes           |
| POST   | `/api/posts/like/{id}`  | Like/Unlike post                 | Yes           |

### Comments
| Method | Endpoint                       | Description                  | Auth Required |
|--------|--------------------------------|------------------------------|---------------|
| POST   | `/api/comments/add/{postId}`   | Add comment to post          | Yes           |
| DELETE | `/api/comments/delete/{id}`    | Delete comment               | Yes           |

### Tags
| Method | Endpoint               | Description         | Auth Required |
|--------|------------------------|---------------------|---------------|
| POST   | `/api/tags/add`         | Create new tag      | Yes           |
| DELETE | `/api/tags/delete/{id}` | Delete tag          | Yes           |

## 👨‍💻 Developer

**Mahmoud Salah**  
💼 [GitHub](https://github.com/MahmoudSalah53)  
📧 Mahmoudsalahmansour53@gmail.com  
