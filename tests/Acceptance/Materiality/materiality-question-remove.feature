Feature:
    I want to make sure that the software allows to remove a materiality question

    Scenario: As an admin, I can remove a materiality question
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality question "¿Pregunta?" with id "q1" for criterion "magnitude" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/remove/q1" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la pregunta de materialidad ¿Pregunta?"
        Then materiality question "q1" should be removed

    Scenario: As normal user I can't remove materiality questions
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given materiality question "¿Pregunta?" with id "q1" for criterion "magnitude" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-questions/remove/q1" with csrf token "remove"
        Then the response status should be 403
