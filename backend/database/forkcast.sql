CREATE DATABASE IF NOT EXISTS forkcast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forkcast;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS planned_meals;
DROP TABLE IF EXISTS recipe_ingredients;
DROP TABLE IF EXISTS meal_plans;
DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Application users (admins and regular users) with hashed passwords.
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recipe categories, for example breakfast, vegetarian or dessert.
CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recipes owned by a user, either written by the user or imported from TheMealDB.
CREATE TABLE recipes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    instructions TEXT NOT NULL,
    prep_time_minutes SMALLINT UNSIGNED NOT NULL,
    servings TINYINT UNSIGNED NOT NULL,
    image_url VARCHAR(500) NULL,
    source ENUM('own', 'mealdb') NOT NULL DEFAULT 'own',
    external_id VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_recipes_user_external (user_id, external_id),
    CONSTRAINT chk_recipes_prep_time_minutes CHECK (prep_time_minutes BETWEEN 1 AND 600),
    CONSTRAINT chk_recipes_servings CHECK (servings BETWEEN 1 AND 20),
    CONSTRAINT fk_recipes_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_recipes_category_id FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ingredient lines that belong to a recipe.
CREATE TABLE recipe_ingredients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    measure VARCHAR(50) NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_recipe_ingredients_recipe_id FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One meal plan per user per week.
CREATE TABLE meal_plans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    week_start_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_meal_plans_user_week (user_id, week_start_date),
    CONSTRAINT fk_meal_plans_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meals scheduled in a plan, one recipe per day and meal type.
CREATE TABLE planned_meals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    plan_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    servings TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uk_planned_meals_slot (plan_id, day_of_week, meal_type),
    CONSTRAINT chk_planned_meals_day_of_week CHECK (day_of_week BETWEEN 1 AND 7),
    CONSTRAINT fk_planned_meals_plan_id FOREIGN KEY (plan_id) REFERENCES meal_plans (id) ON DELETE CASCADE,
    CONSTRAINT fk_planned_meals_recipe_id FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
