-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2026 at 06:58 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `forkcast`
--
CREATE DATABASE IF NOT EXISTS `forkcast` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `forkcast`;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Beef', 'Cuts of beef and beef-based dishes.'),
(2, 'Breakfast', 'Morning meals and brunch ideas.'),
(3, 'Chicken', 'Chicken dishes for every day.'),
(4, 'Dessert', 'Sweet treats and baked desserts.'),
(5, 'Goat', 'Goat meat stews and roasts.'),
(6, 'Lamb', 'Lamb roasts, stews and grills.'),
(7, 'Miscellaneous', 'Recipes that fit no other category.'),
(8, 'Pasta', 'Pasta dishes and sauces.'),
(9, 'Pork', 'Pork dishes and charcuterie.'),
(10, 'Seafood', 'Fish and shellfish dishes.'),
(11, 'Side', 'Side dishes to complete a meal.'),
(12, 'Starter', 'Small dishes to start a meal.'),
(13, 'Vegan', 'Recipes with no animal products.'),
(14, 'Vegetarian', 'Meat-free recipes with dairy or eggs.');

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

DROP TABLE IF EXISTS `meal_plans`;
CREATE TABLE `meal_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `week_start_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`id`, `user_id`, `name`, `week_start_date`, `created_at`) VALUES
(1, 2, 'Week of 26 Oct', '2026-10-26', '2026-11-01 16:31:33'),
(2, 3, 'Week of 2 Nov', '2026-11-02', '2026-11-01 16:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `planned_meals`
--

DROP TABLE IF EXISTS `planned_meals`;
CREATE TABLE `planned_meals` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `servings` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `planned_meals`
--

INSERT INTO `planned_meals` (`id`, `plan_id`, `recipe_id`, `day_of_week`, `meal_type`, `servings`) VALUES
(1, 1, 1, 1, 'dinner', 2),
(2, 1, 4, 2, 'breakfast', 2),
(3, 1, 3, 3, 'lunch', 2),
(4, 1, 8, 4, 'dinner', 3),
(5, 2, 2, 1, 'dinner', 4),
(6, 2, 5, 2, 'lunch', 4),
(7, 2, 6, 3, 'snack', 8),
(8, 2, 7, 5, 'dinner', 2);

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
CREATE TABLE `recipes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `instructions` text NOT NULL,
  `prep_time_minutes` smallint(5) UNSIGNED NOT NULL,
  `servings` tinyint(3) UNSIGNED NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `source` enum('own','mealdb') NOT NULL DEFAULT 'own',
  `external_id` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `category_id`, `title`, `instructions`, `prep_time_minutes`, `servings`, `image_url`, `source`, `external_id`, `created_at`) VALUES
(1, 2, 8, 'Spaghetti Carbonara', 'Bring a large pot of salted water to the boil and cook the spaghetti until al dente. While the pasta cooks, cut the pancetta into small strips and fry it in a dry pan until crisp. Whisk the eggs with the grated pecorino and a generous amount of black pepper in a bowl. Drain the pasta and reserve a cup of the cooking water. Toss the hot pasta with the pancetta, then remove the pan from the heat and stir in the egg mixture. Add a splash of pasta water to loosen the sauce and serve immediately with extra cheese.', 25, 4, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(2, 3, 3, 'Chicken Curry', 'Heat the oil in a large pan and soften the diced onion for five minutes. Add the garlic, ginger and curry powder and fry for one minute until fragrant. Stir in the chicken pieces and cook until they are sealed on all sides. Pour in the chopped tomatoes and coconut milk, then simmer gently for twenty minutes. Season with salt and finish with a handful of fresh coriander. Serve the curry with basmati rice and warm naan bread.', 45, 4, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(3, 2, 14, 'Greek Salad', 'Cut the tomatoes into wedges and the cucumber into thick half moons. Thinly slice the red onion and soak it in cold water for five minutes to soften the bite. Combine the vegetables in a large bowl with the olives and the feta cut into cubes. Whisk the olive oil, red wine vinegar and dried oregano into a simple dressing. Pour the dressing over the salad, season with salt and pepper and toss gently before serving.', 15, 2, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(4, 2, 2, 'Fluffy Pancakes', 'Whisk the flour, baking powder, sugar and a pinch of salt together in a large bowl. Beat the milk, eggs and melted butter in a jug, then pour the wet mix into the dry ingredients. Stir just until combined, because a few small lumps keep the pancakes light. Rest the batter for ten minutes while a non-stick pan heats over medium heat. Ladle the batter into the pan and cook each pancake for two minutes per side until golden. Stack the pancakes and serve them with maple syrup and fresh berries.', 20, 4, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(5, 3, 1, 'Beef Tacos', 'Finely chop the onion and fry it in a hot pan until translucent. Add the minced beef and break it up with a wooden spoon until it browns. Stir in the tomato paste, cumin, paprika and a splash of water and simmer for ten minutes. Warm the tortillas in a dry pan for a few seconds on each side. Fill each tortilla with the beef and top with shredded lettuce, diced tomato and grated cheese. Serve with lime wedges and sour cream on the side.', 35, 4, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(6, 2, 4, 'Chocolate Brownies', 'Preheat the oven to 180 degrees and line a square baking tin with baking paper. Melt the dark chocolate and butter together in a bowl set over simmering water. Whisk the eggs and sugar until pale and thick, then fold in the chocolate mixture. Sift in the flour and cocoa powder and fold gently until just combined. Pour the batter into the tin and bake for twenty to twenty-five minutes, leaving the centre slightly soft. Cool the brownies completely in the tin before cutting them into squares.', 40, 8, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(7, 3, 10, 'Grilled Salmon', 'Pat the salmon fillets dry and brush them with olive oil. Mix the lemon juice, crushed garlic and chopped dill and spoon it over the fish. Season with salt and pepper and leave the fillets to marinate for ten minutes. Heat a grill pan until it is very hot and grill the salmon skin side down for four minutes. Turn the fillets carefully and cook for another three minutes until the fish flakes easily. Serve with steamed asparagus and lemon wedges.', 25, 2, NULL, 'own', NULL, '2026-11-01 16:31:33'),
(8, 2, 13, 'Veggie Stir-fry', 'Cook the rice noodles according to the packet instructions and drain them well. Prepare the broccoli, carrots, red pepper and spring onions and cut them into bite-sized pieces. Heat the sesame oil in a wok over high heat and stir-fry the firm vegetables for three minutes. Add the pepper and spring onions and cook for another two minutes so they stay crisp. Pour in the soy sauce, sweet chilli sauce and a little water and toss everything together. Serve the stir-fry over the noodles and scatter with sesame seeds.', 20, 3, NULL, 'own', NULL, '2026-11-01 16:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredients`
--

DROP TABLE IF EXISTS `recipe_ingredients`;
CREATE TABLE `recipe_ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `measure` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `name`, `measure`) VALUES
(1, 1, 'Spaghetti', '400 g'),
(2, 1, 'Pancetta', '200 g'),
(3, 1, 'Eggs', '4'),
(4, 1, 'Pecorino Romano', '100 g'),
(5, 1, 'Black pepper', '1 tsp'),
(6, 2, 'Chicken breast', '600 g'),
(7, 2, 'Onion', '2'),
(8, 2, 'Garlic', '3 cloves'),
(9, 2, 'Curry powder', '2 tbsp'),
(10, 2, 'Chopped tomatoes', '400 g'),
(11, 2, 'Coconut milk', '400 ml'),
(12, 3, 'Tomatoes', '4'),
(13, 3, 'Cucumber', '1'),
(14, 3, 'Red onion', '1'),
(15, 3, 'Feta cheese', '200 g'),
(16, 3, 'Black olives', '100 g'),
(17, 3, 'Olive oil', '4 tbsp'),
(18, 4, 'Plain flour', '200 g'),
(19, 4, 'Baking powder', '2 tsp'),
(20, 4, 'Milk', '300 ml'),
(21, 4, 'Eggs', '2'),
(22, 4, 'Butter', '50 g'),
(23, 4, 'Maple syrup', '4 tbsp'),
(24, 5, 'Minced beef', '500 g'),
(25, 5, 'Onion', '1'),
(26, 5, 'Taco seasoning', '2 tbsp'),
(27, 5, 'Tortillas', '8'),
(28, 5, 'Lettuce', '1 head'),
(29, 5, 'Cheddar cheese', '100 g'),
(30, 6, 'Dark chocolate', '200 g'),
(31, 6, 'Butter', '150 g'),
(32, 6, 'Eggs', '3'),
(33, 6, 'Sugar', '250 g'),
(34, 6, 'Plain flour', '120 g'),
(35, 6, 'Cocoa powder', '30 g'),
(36, 7, 'Salmon fillets', '2'),
(37, 7, 'Lemon', '1'),
(38, 7, 'Garlic', '2 cloves'),
(39, 7, 'Fresh dill', '2 tbsp'),
(40, 7, 'Olive oil', '2 tbsp'),
(41, 8, 'Rice noodles', '200 g'),
(42, 8, 'Broccoli', '1 head'),
(43, 8, 'Carrots', '2'),
(44, 8, 'Red pepper', '1'),
(45, 8, 'Soy sauce', '3 tbsp'),
(46, 8, 'Sesame oil', '1 tbsp');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Ada', 'Admin', 'admin@forkcast.test', '$2y$12$0Mt965ggXlaCgeBQbjZoXekzIxiaM7f86O4dgeO05pkh9qqx452rO', 'admin', '2026-11-01 16:31:33'),
(2, 'Una', 'User', 'user1@forkcast.test', '$2y$12$fPCCNarufblO0fjuGgPxK.QOtW5BH26JvgYCmBuWjHgPpQZZcReJS', 'user', '2026-11-01 16:31:33'),
(3, 'Marko', 'User', 'user2@forkcast.test', '$2y$12$7tekKN2KfYNejdNmKkzYt.GpQVTjG.06JCzJEVur73kMXJmotkOha', 'user', '2026-11-01 16:31:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_categories_name` (`name`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_meal_plans_user_week` (`user_id`,`week_start_date`);

--
-- Indexes for table `planned_meals`
--
ALTER TABLE `planned_meals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_planned_meals_slot` (`plan_id`,`day_of_week`,`meal_type`),
  ADD KEY `fk_planned_meals_recipe_id` (`recipe_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_recipes_user_external` (`user_id`,`external_id`),
  ADD KEY `fk_recipes_category_id` (`category_id`);

--
-- Indexes for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recipe_ingredients_recipe_id` (`recipe_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `planned_meals`
--
ALTER TABLE `planned_meals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD CONSTRAINT `fk_meal_plans_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `planned_meals`
--
ALTER TABLE `planned_meals`
  ADD CONSTRAINT `fk_planned_meals_plan_id` FOREIGN KEY (`plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_planned_meals_recipe_id` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`);

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `fk_recipes_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_recipes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `fk_recipe_ingredients_recipe_id` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
