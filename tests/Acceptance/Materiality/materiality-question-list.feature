Feature:
    I want to make sure that the software allows to list the materiality questions

    Scenario: As an admin, I can list the materiality questions
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality question "¿Pregunta genérica?" with id "q1" for criterion "magnitude" exists
        Given materiality question "¿Pregunta específica?" with id "q2" for criterion "risk" and topic "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions"
        Then the response status should be 200
        Then I see a text "¿Pregunta genérica?"
        Then I see a text "Todas las categorías"
        Then I see a text "¿Pregunta específica?"
        Then I see a text "Lavandería externa"

    Scenario: As an admin, I can filter the materiality questions by criterion
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality question "¿Pregunta de magnitud?" with id "q1" for criterion "magnitude" exists
        Given materiality question "¿Pregunta de riesgo?" with id "q2" for criterion "risk" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions"
        When I filter by "criterion" with "risk"
        Then the response status should be 200
        Then I see a text "¿Pregunta de riesgo?"
        Then I do not see a text "¿Pregunta de magnitud?"
        Then I see a text "<b>Riesgo</b>"

    Scenario: As an admin, I can filter the materiality questions by topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality question "¿Pregunta genérica?" with id "q1" for criterion "magnitude" exists
        Given materiality question "¿Pregunta específica?" with id "q2" for criterion "risk" and topic "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions"
        When I filter by "topicId" with "111"
        Then the response status should be 200
        Then I see a text "¿Pregunta específica?"
        Then I do not see a text "¿Pregunta genérica?"

    Scenario: As an admin, I can filter the materiality questions by an inactive topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality topic "111" is inactive
        Given materiality question "¿Pregunta genérica?" with id "q1" for criterion "magnitude" exists
        Given materiality question "¿Pregunta específica?" with id "q2" for criterion "risk" and topic "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions"
        When I filter by "topicId" with "111"
        Then the response status should be 200
        Then I see a text "¿Pregunta específica?"
        Then I do not see a text "¿Pregunta genérica?"

    Scenario: As normal user I can't list the materiality questions
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-questions"
        Then the response status should be 403
