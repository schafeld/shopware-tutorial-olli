SET @version_id = 0x0FA91CE3E96A4BC2BE4BD9CE752C3425;

-- Clear existing and add comprehensive recommendations
DELETE FROM learning_product_recommendation;

-- Recommendations for 019BF958CFEC701489B6F06A6E41EDBC
INSERT INTO learning_product_recommendation 
(id, source_product_id, source_product_version_id, recommended_product_id, recommended_product_version_id, affinity_score, view_count, last_updated, created_at)
VALUES 
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958CFEC701489B6F06A6E41EDBC, @version_id, 0x019BF958D4F673038EADC1BEB64079FD, @version_id, 0.90, 3, NOW(3), NOW(3)),
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958CFEC701489B6F06A6E41EDBC, @version_id, 0x019BF958D35D73099E5194AB4299E016, @version_id, 0.85, 2, NOW(3), NOW(3));

-- Recommendations for 019BF958D4F673038EADC1BEB64079FD
INSERT INTO learning_product_recommendation 
(id, source_product_id, source_product_version_id, recommended_product_id, recommended_product_version_id, affinity_score, view_count, last_updated, created_at)
VALUES 
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D4F673038EADC1BEB64079FD, @version_id, 0x019BF958CFEC701489B6F06A6E41EDBC, @version_id, 0.90, 3, NOW(3), NOW(3)),
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D4F673038EADC1BEB64079FD, @version_id, 0x019BF958D35D73099E5194AB4299E016, @version_id, 0.80, 2, NOW(3), NOW(3));

-- Recommendations for 019BF958D35D73099E5194AB4299E016
INSERT INTO learning_product_recommendation 
(id, source_product_id, source_product_version_id, recommended_product_id, recommended_product_version_id, affinity_score, view_count, last_updated, created_at)
VALUES 
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D35D73099E5194AB4299E016, @version_id, 0x019BF958CFEC701489B6F06A6E41EDBC, @version_id, 0.85, 2, NOW(3), NOW(3)),
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D35D73099E5194AB4299E016, @version_id, 0x019BF958D4F673038EADC1BEB64079FD, @version_id, 0.80, 2, NOW(3), NOW(3));

-- Add more from session 6mog6p1rl55ef3ufte8elavbpi
INSERT INTO learning_product_recommendation 
(id, source_product_id, source_product_version_id, recommended_product_id, recommended_product_version_id, affinity_score, view_count, last_updated, created_at)
VALUES 
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D323700499774EA0A2DBE6B4, @version_id, 0x019BF958D1CF71B8B5C8F4B970A125E3, @version_id, 0.85, 4, NOW(3), NOW(3)),
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D323700499774EA0A2DBE6B4, @version_id, 0x019BF958D2E4725F8DF045161BF4BF7C, @version_id, 0.75, 2, NOW(3), NOW(3)),
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D1CF71B8B5C8F4B970A125E3, @version_id, 0x019BF958D323700499774EA0A2DBE6B4, @version_id, 0.85, 4, NOW(3), NOW(3)),
(UNHEX(REPLACE(UUID(), '-', '')), 0x019BF958D2E4725F8DF045161BF4BF7C, @version_id, 0x019BF958D323700499774EA0A2DBE6B4, @version_id, 0.75, 2, NOW(3), NOW(3));

SELECT COUNT(*) as total_recommendations FROM learning_product_recommendation;
