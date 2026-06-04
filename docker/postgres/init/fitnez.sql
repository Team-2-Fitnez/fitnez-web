BEGIN;

CREATE TABLE IF NOT EXISTS public.roles
(
    id serial NOT NULL,
    name character varying(50) NOT NULL,
    description text,
    CONSTRAINT roles_pkey PRIMARY KEY (id),
    CONSTRAINT roles_name_key UNIQUE (name)
);

CREATE TABLE IF NOT EXISTS public.users
(
    id serial NOT NULL,
    email character varying(255) NOT NULL,
    password_hash character varying(255) NOT NULL,
    full_name character varying(100) NOT NULL,
    birth_date date,
    phone character varying(20),
    profile_picture_url text,
    role_id integer NOT NULL,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    last_login timestamp with time zone,
    is_active boolean DEFAULT true,
    email_verified_at timestamp with time zone,
    CONSTRAINT users_pkey PRIMARY KEY (id),
    CONSTRAINT users_email_key UNIQUE (email)
);

CREATE TABLE IF NOT EXISTS public.authentications
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    email character varying(255),
    password_hash character varying(255),
    provider character varying(50) DEFAULT 'local',
    last_login timestamp with time zone,
    is_active boolean DEFAULT true,
    failed_login_attempts integer DEFAULT 0,
    locked_until timestamp with time zone,
    password_updated_at timestamp with time zone,
    CONSTRAINT authentications_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.exercises
(
    id serial NOT NULL,
    name character varying(100) NOT NULL,
    muscle_group character varying(50),
    equipment character varying(50),
    tutorial_video_url text,
    tutorial_image_url text,
    instructions text,
    CONSTRAINT exercises_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.faq
(
    id serial NOT NULL,
    question text NOT NULL,
    answer text NOT NULL,
    category character varying(255),
    display_order integer DEFAULT 0,
    is_active boolean DEFAULT true,
    CONSTRAINT faq_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.member_details
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    height_cm integer,
    weight_kg integer,
    fitness_goals character varying(255),
    experience_level character varying(255),
    CONSTRAINT member_details_pkey PRIMARY KEY (id),
    CONSTRAINT member_details_user_id_key UNIQUE (user_id)
);

CREATE TABLE IF NOT EXISTS public.nutrition_calculator
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    bmr numeric(7, 2),
    tdee numeric(7, 2),
    target_calories numeric(7, 2),
    target_protein numeric(7, 2),
    target_carbs numeric(7, 2),
    target_fat numeric(7, 2),
    calculated_at timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT nutrition_calculator_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.meal_plans
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    title character varying(100) NOT NULL,
    total_calories numeric(7, 2),
    protein_grams numeric(7, 2),
    carbs_grams numeric(7, 2),
    fat_grams numeric(7, 2),
    plan_date date NOT NULL,
    CONSTRAINT meal_plans_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.meals
(
    id serial NOT NULL,
    meal_plan_id integer NOT NULL,
    meal_type character varying(255) NOT NULL,
    food_name character varying(100) NOT NULL,
    portion_grams integer,
    calories numeric(7, 2),
    protein numeric(7, 2),
    carbs numeric(7, 2),
    fat numeric(7, 2),
    CONSTRAINT meals_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.workout_plans
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    title character varying(100) NOT NULL,
    description text,
    start_date date,
    end_date date,
    status character varying(255),
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    updated_at timestamp with time zone,
    CONSTRAINT workout_plans_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.workout_exercises
(
    id serial NOT NULL,
    workout_plan_id integer NOT NULL,
    exercise_id integer NOT NULL,
    day_of_week integer NOT NULL,
    sets integer NOT NULL,
    reps integer NOT NULL,
    rest_seconds integer,
    notes text,
    CONSTRAINT workout_exercises_pkey PRIMARY KEY (id),
    CONSTRAINT workout_exercises_workout_plan_id_exercise_id_day_of_week_key UNIQUE (workout_plan_id, exercise_id, day_of_week)
);

CREATE TABLE IF NOT EXISTS public.workout_trackings
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    workout_exercise_id integer NOT NULL,
    workout_date date NOT NULL,
    actual_sets integer,
    actual_reps integer,
    actual_weight_kg integer,
    is_completed boolean DEFAULT false,
    logged_at timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT workout_trackings_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.trainer_details
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    specialization character varying(100),
    biography text,
    experience_years integer,
    hourly_rate numeric(10, 2),
    avg_rating double precision DEFAULT 0,
    CONSTRAINT trainer_details_pkey PRIMARY KEY (id),
    CONSTRAINT trainer_details_user_id_key UNIQUE (user_id)
);

CREATE TABLE IF NOT EXISTS public.trainer_applications
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    cv_document_url text NOT NULL,
    certificate_document_url text,
    status character varying(255) NOT NULL,
    submitted_at timestamp with time zone NOT NULL DEFAULT now(),
    reviewed_at timestamp with time zone,
    reviewed_by_admin_id integer,
    admin_notes text,
    CONSTRAINT trainer_applications_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.trainer_bookings
(
    id serial NOT NULL,
    member_id integer NOT NULL,
    trainer_id integer NOT NULL,
    booking_date date NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL,
    session_type character varying(255) NOT NULL,
    location character varying(255),
    member_notes text,
    status character varying(255),
    total_price numeric(10, 2) NOT NULL,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT trainer_bookings_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.attendance
(
    id serial NOT NULL,
    user_id integer NOT NULL,
    check_in_time timestamp with time zone NOT NULL,
    check_out_time timestamp with time zone,
    attendance_type character varying(20) NOT NULL,
    booking_id integer,
    CONSTRAINT attendance_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.payments
(
    id serial NOT NULL,
    invoice_number character varying(50) NOT NULL,
    user_id integer NOT NULL,
    booking_id integer,
    payment_type character varying(255) NOT NULL,
    amount numeric(10, 2) NOT NULL,
    payment_method character varying(255) NOT NULL,
    payment_status character varying(255),
    payment_date timestamp with time zone,
    external_reference character varying(100),
    CONSTRAINT payments_pkey PRIMARY KEY (id),
    CONSTRAINT payments_invoice_number_key UNIQUE (invoice_number)
);

CREATE TABLE IF NOT EXISTS public.trainer_earnings
(
    id serial NOT NULL,
    trainer_id integer NOT NULL,
    payment_id integer NOT NULL,
    booking_id integer NOT NULL,
    commission_rate numeric(5, 2) NOT NULL,
    trainer_amount numeric(10, 2) NOT NULL,
    status character varying(255),
    disbursed_at timestamp with time zone,
    CONSTRAINT trainer_earnings_pkey PRIMARY KEY (id),
    CONSTRAINT trainer_earnings_payment_id_booking_id_key UNIQUE (payment_id, booking_id)
);

CREATE TABLE IF NOT EXISTS public.notifications
(
    id serial NOT NULL,
    user_id integer,
    title character varying(255) NOT NULL,
    body text NOT NULL,
    notification_type character varying(255) NOT NULL,
    is_read boolean DEFAULT false,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT notifications_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.chat_messages
(
    id serial NOT NULL,
    sender_id integer NOT NULL,
    receiver_id integer NOT NULL,
    booking_id integer,
    message text NOT NULL,
    is_read boolean DEFAULT false,
    sent_at timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT chat_messages_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS public.system_logs
(
    id serial NOT NULL,
    user_id integer,
    action_type character varying(255) NOT NULL,
    table_affected character varying(50),
    record_id integer,
    description text,
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT system_logs_pkey PRIMARY KEY (id)
);

ALTER TABLE IF EXISTS public.users
ADD CONSTRAINT users_role_id_fkey FOREIGN KEY (role_id)
REFERENCES public.roles (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.authentications
ADD CONSTRAINT authentications_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.member_details
ADD CONSTRAINT member_details_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.nutrition_calculator
ADD CONSTRAINT nutrition_calculator_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.meal_plans
ADD CONSTRAINT meal_plans_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.meals
ADD CONSTRAINT meals_meal_plan_id_fkey FOREIGN KEY (meal_plan_id)
REFERENCES public.meal_plans (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.workout_plans
ADD CONSTRAINT workout_plans_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.workout_exercises
ADD CONSTRAINT workout_exercises_exercise_id_fkey FOREIGN KEY (exercise_id)
REFERENCES public.exercises (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.workout_exercises
ADD CONSTRAINT workout_exercises_workout_plan_id_fkey FOREIGN KEY (workout_plan_id)
REFERENCES public.workout_plans (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.workout_trackings
ADD CONSTRAINT workout_trackings_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.workout_trackings
ADD CONSTRAINT workout_trackings_workout_exercise_id_fkey FOREIGN KEY (workout_exercise_id)
REFERENCES public.workout_exercises (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.trainer_details
ADD CONSTRAINT trainer_details_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.trainer_applications
ADD CONSTRAINT trainer_applications_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.trainer_applications
ADD CONSTRAINT trainer_applications_reviewed_by_admin_id_fkey FOREIGN KEY (reviewed_by_admin_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE SET NULL;

ALTER TABLE IF EXISTS public.trainer_bookings
ADD CONSTRAINT trainer_bookings_member_id_fkey FOREIGN KEY (member_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.trainer_bookings
ADD CONSTRAINT trainer_bookings_trainer_id_fkey FOREIGN KEY (trainer_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.attendance
ADD CONSTRAINT attendance_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.attendance
ADD CONSTRAINT attendance_booking_id_fkey FOREIGN KEY (booking_id)
REFERENCES public.trainer_bookings (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE SET NULL;

ALTER TABLE IF EXISTS public.payments
ADD CONSTRAINT payments_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.payments
ADD CONSTRAINT payments_booking_id_fkey FOREIGN KEY (booking_id)
REFERENCES public.trainer_bookings (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE SET NULL;

ALTER TABLE IF EXISTS public.trainer_earnings
ADD CONSTRAINT trainer_earnings_trainer_id_fkey FOREIGN KEY (trainer_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.trainer_earnings
ADD CONSTRAINT trainer_earnings_payment_id_fkey FOREIGN KEY (payment_id)
REFERENCES public.payments (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.trainer_earnings
ADD CONSTRAINT trainer_earnings_booking_id_fkey FOREIGN KEY (booking_id)
REFERENCES public.trainer_bookings (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE RESTRICT;

ALTER TABLE IF EXISTS public.notifications
ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.chat_messages
ADD CONSTRAINT chat_messages_sender_id_fkey FOREIGN KEY (sender_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.chat_messages
ADD CONSTRAINT chat_messages_receiver_id_fkey FOREIGN KEY (receiver_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE CASCADE;

ALTER TABLE IF EXISTS public.chat_messages
ADD CONSTRAINT chat_messages_booking_id_fkey FOREIGN KEY (booking_id)
REFERENCES public.trainer_bookings (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE SET NULL;

ALTER TABLE IF EXISTS public.system_logs
ADD CONSTRAINT system_logs_user_id_fkey FOREIGN KEY (user_id)
REFERENCES public.users (id) MATCH SIMPLE
ON UPDATE NO ACTION
ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_users_role_id ON public.users(role_id);
CREATE INDEX IF NOT EXISTS idx_authentications_user_id ON public.authentications(user_id);
CREATE INDEX IF NOT EXISTS idx_member_details_user_id ON public.member_details(user_id);
CREATE INDEX IF NOT EXISTS idx_nutrition_calculator_user_id ON public.nutrition_calculator(user_id);
CREATE INDEX IF NOT EXISTS idx_meal_plans_user_id ON public.meal_plans(user_id);
CREATE INDEX IF NOT EXISTS idx_meals_meal_plan_id ON public.meals(meal_plan_id);
CREATE INDEX IF NOT EXISTS idx_workout_plans_user_id ON public.workout_plans(user_id);
CREATE INDEX IF NOT EXISTS idx_workout_exercises_exercise_id ON public.workout_exercises(exercise_id);
CREATE INDEX IF NOT EXISTS idx_workout_exercises_plan_id ON public.workout_exercises(workout_plan_id);
CREATE INDEX IF NOT EXISTS idx_workout_trackings_user_id ON public.workout_trackings(user_id);
CREATE INDEX IF NOT EXISTS idx_workout_trackings_workout_exercise_id ON public.workout_trackings(workout_exercise_id);
CREATE INDEX IF NOT EXISTS idx_trainer_details_user_id ON public.trainer_details(user_id);
CREATE INDEX IF NOT EXISTS idx_trainer_applications_user_id ON public.trainer_applications(user_id);
CREATE INDEX IF NOT EXISTS idx_trainer_bookings_member_id ON public.trainer_bookings(member_id);
CREATE INDEX IF NOT EXISTS idx_trainer_bookings_trainer_id ON public.trainer_bookings(trainer_id);
CREATE INDEX IF NOT EXISTS idx_attendance_user_id ON public.attendance(user_id);
CREATE INDEX IF NOT EXISTS idx_attendance_booking_id ON public.attendance(booking_id);
CREATE INDEX IF NOT EXISTS idx_payments_user_id ON public.payments(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_booking_id ON public.payments(booking_id);
CREATE INDEX IF NOT EXISTS idx_trainer_earnings_trainer_id ON public.trainer_earnings(trainer_id);
CREATE INDEX IF NOT EXISTS idx_trainer_earnings_payment_id ON public.trainer_earnings(payment_id);
CREATE INDEX IF NOT EXISTS idx_trainer_earnings_booking_id ON public.trainer_earnings(booking_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON public.notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_sender_id ON public.chat_messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_receiver_id ON public.chat_messages(receiver_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_booking_id ON public.chat_messages(booking_id);
CREATE INDEX IF NOT EXISTS idx_system_logs_user_id ON public.system_logs(user_id);


-- Fitnez demo accounts for group testing.
-- Password for all accounts: FitnezTeam2@2026
INSERT INTO public.roles (name, description) VALUES
('admin', 'Fitnez administrator'),
('member', 'Fitnez member/user'),
('trainer', 'Legacy role kept only for backward compatibility')
ON CONFLICT (name) DO UPDATE SET description = EXCLUDED.description;

DO $$
DECLARE
    admin_role_id integer;
    member_role_id integer;
    admin_user_id integer;
    trainer_user_id integer;
BEGIN
    SELECT id INTO admin_role_id FROM public.roles WHERE name = 'admin';
    SELECT id INTO member_role_id FROM public.roles WHERE name = 'member';

    INSERT INTO public.users (email, password_hash, full_name, phone, role_id, is_active, email_verified_at)
    VALUES ('admin@fitnez.test', '$2y$12$6tZEpVsgv/mXinZSJNxOp.sgGGSTsKRU.CD0FPegCeHB7TwNCJUxy', 'Fitnez Admin', '080000000001', admin_role_id, true, now())
    ON CONFLICT (email) DO UPDATE SET
        password_hash = EXCLUDED.password_hash,
        full_name = EXCLUDED.full_name,
        phone = EXCLUDED.phone,
        role_id = EXCLUDED.role_id,
        is_active = true,
        email_verified_at = now()
    RETURNING id INTO admin_user_id;

    INSERT INTO public.users (email, password_hash, full_name, phone, role_id, is_active, email_verified_at)
    VALUES ('trainer@fitnez.test', '$2y$12$6tZEpVsgv/mXinZSJNxOp.sgGGSTsKRU.CD0FPegCeHB7TwNCJUxy', 'Fitnez Approved Trainer', '080000000002', member_role_id, true, now())
    ON CONFLICT (email) DO UPDATE SET
        password_hash = EXCLUDED.password_hash,
        full_name = EXCLUDED.full_name,
        phone = EXCLUDED.phone,
        role_id = EXCLUDED.role_id,
        is_active = true,
        email_verified_at = now()
    RETURNING id INTO trainer_user_id;

    INSERT INTO public.users (email, password_hash, full_name, phone, role_id, is_active, email_verified_at)
    VALUES ('member@fitnez.test', '$2y$12$6tZEpVsgv/mXinZSJNxOp.sgGGSTsKRU.CD0FPegCeHB7TwNCJUxy', 'Fitnez Member', '080000000003', member_role_id, true, now())
    ON CONFLICT (email) DO UPDATE SET
        password_hash = EXCLUDED.password_hash,
        full_name = EXCLUDED.full_name,
        phone = EXCLUDED.phone,
        role_id = EXCLUDED.role_id,
        is_active = true,
        email_verified_at = now();

    IF trainer_user_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.trainer_applications WHERE user_id = trainer_user_id AND status = 'approved') THEN
            INSERT INTO public.trainer_applications (user_id, cv_document_url, certificate_document_url, status, submitted_at, reviewed_at, reviewed_by_admin_id, admin_notes)
            VALUES (trainer_user_id, 'dummy/approved-trainer-cv.pdf', 'dummy/approved-trainer-certificate.pdf', 'approved', now(), now(), admin_user_id, 'Dummy trainer account for team testing.');
        END IF;

        INSERT INTO public.trainer_details (user_id, specialization, biography, experience_years, hourly_rate, avg_rating)
        VALUES (trainer_user_id, 'Strength Training', 'Dummy approved trainer account for Fitnez testing.', 3, 150000, 4.8)
        ON CONFLICT (user_id) DO UPDATE SET
            specialization = EXCLUDED.specialization,
            biography = EXCLUDED.biography,
            experience_years = EXCLUDED.experience_years,
            hourly_rate = EXCLUDED.hourly_rate,
            avg_rating = EXCLUDED.avg_rating;
    END IF;
END $$;

END;