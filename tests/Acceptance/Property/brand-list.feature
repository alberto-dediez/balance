Feature:
    Ability to list brands in web

    Scenario: As admin I can access a list of all brands
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given brand "Orizonia" with id "123" exists
        Given brand "Burger King" with id "456" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/brands"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all brands
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given brand "Orizonia" with id "123" exists
        Given brand "Burger King" with id "456" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/brands"
        Then the response status should be 403