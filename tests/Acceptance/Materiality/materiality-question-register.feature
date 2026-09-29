Feature:
    I want to make sure that the software allows to register a materiality question

    Scenario: As an admin, I can access to register new materiality questions
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new materiality questions
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        Then the response status should be 403

    Scenario: As an admin, I can register a generic materiality question
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        When I submit a form with "criterion:risk|topicId:|text:¿Pregunta genérica?|answerScale:level|weight:2|position:1" and button "Registrar"
        Then I am redirected to route "admin_materiality_questions"
        Then the response status should be 200
        Then I see a text "Pregunta de materialidad creada correctamente: ¿Pregunta genérica?"
        Then there should be 1 materiality questions

    Scenario: As an admin, I can register a materiality question for a topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        When I submit a form with "criterion:outsourcing|topicId:111|text:¿Pregunta específica?|answerScale:yes_no|weight:1|position:1" and button "Registrar"
        Then I am redirected to route "admin_materiality_questions"
        When I go to "/es/admin/materiality-questions"
        Then I see a text "¿Pregunta específica?"
        Then I see a text "Lavandería externa"

    Scenario: As an admin, I can't register a materiality question with weight 0
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        When I submit a form with "criterion:risk|topicId:|text:¿Pregunta?|answerScale:level|weight:0|position:1" and button "Registrar"
        Then the response status should be 200
        Then I see a form
        Then there should be 0 materiality questions

    Scenario: As an admin, I can register a question for two sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        Given materiality sector "Eventos" with id "222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        When I submit a form with "criterion:risk|topicId:|sectorIds:[222,111]|text:¿Catering externo?|answerScale:level|weight:1|position:1" and button "Registrar"
        Then I am redirected to route "admin_materiality_questions"
        Then the response status should be 200
        When I go to "/es/admin/materiality-questions"
        Then I see a text "¿Catering externo?"
        Then I see a text "Eventos, Hoteles"

    Scenario: A question without sectors applies to all sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/register"
        When I submit a form with "criterion:risk|topicId:|text:¿Pregunta genérica?|answerScale:level|weight:1|position:1" and button "Registrar"
        Then I am redirected to route "admin_materiality_questions"
        When I go to "/es/admin/materiality-questions"
        Then I see a text "¿Pregunta genérica?"
        Then I see a text "Todos los sectores"
