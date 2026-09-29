Feature:
    I want to make sure that the software allows to remove a materiality sector

    Scenario: As an admin, I can remove a materiality sector
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors/remove/111" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el sector de materialidad Hoteles"
        Then materiality sector "111" should be removed

    Scenario: As normal user I can't remove materiality sectors
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given materiality sector "Hoteles" with id "111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/materiality-sectors/remove/111" with csrf token "remove"
        Then the response status should be 403

    Scenario: Removing a sector unlinks it or removes single-sector questions
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality sector "Hoteles" with id "111" exists
        Given materiality sector "Eventos" with id "222" exists
        Given materiality question "¿Catering externo?" with id "q1" for criterion "risk" and sectors "111,222" exists
        Given materiality question "¿Lavandería externa?" with id "q2" for criterion "risk" and sectors "111" exists
        Given materiality question "¿Magnitud?" with id "q3" for criterion "magnitude" exists
        Given materiality question "¿Montaje?" with id "q4" for criterion "risk" and sectors "222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-sectors/remove/111" with csrf token "remove"
        Then materiality sector "111" should be removed
        Then materiality question "q1" should have sectors "222"
        Then materiality question "q2" should be removed
        Then materiality question "q3" should apply to all sectors
        Then materiality question "q4" should have sectors "222"
        Then there should be 3 materiality questions
