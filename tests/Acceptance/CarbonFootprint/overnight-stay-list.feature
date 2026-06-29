Feature:
    Ability to list overnight stays in web

    Scenario: As admin I can access a list of all overnight stays
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given overnight stay "Pernoctación 1" with id "1111" exists
        Given overnight stay "Pernoctación 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/overnight-stays"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all overnight stays
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given overnight stay "Pernoctación 1" with id "1111" exists
        Given overnight stay "Pernoctación 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/overnight-stays"
        Then the response status should be 403
