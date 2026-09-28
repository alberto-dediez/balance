Feature:
    I want to make sure that the software allows to remove a materiality topic

    Scenario: As an admin, I can remove an own materiality topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/remove/111" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la categoría de materialidad Lavandería externa"
        Then materiality topic "111" should be removed

    Scenario: As an admin, I can't remove a standard materiality topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given standard materiality topic "6" with id "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        Then I do not see a text "/es/admin/materiality-topics/remove/666"
        When I go to "/es/admin/materiality-topics/remove/666" with csrf token "remove"
        Then the response status should be 403

    Scenario: Removing a materiality topic removes its specific questions
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality topic "Otra categoría" with id "222" exists
        Given materiality question "¿Específica?" with id "q1" for criterion "risk" and topic "111" exists
        Given materiality question "¿De otra?" with id "q2" for criterion "risk" and topic "222" exists
        Given materiality question "¿Genérica?" with id "q3" for criterion "magnitude" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics/remove/111" with csrf token "remove"
        Then materiality topic "111" should be removed
        Then materiality question "q1" should be removed
        Then there should be 2 materiality questions

    Scenario: As normal user I can't remove materiality topics
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-topics/remove/111" with csrf token "remove"
        Then the response status should be 403
