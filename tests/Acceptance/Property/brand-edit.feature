Feature:
    I want to make sure that the system is allow to access the details of a brand

    Scenario: As an admin, I can access to edit brands
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given brand "Goiko" with id "123" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/brands/edit/123"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't edit brands
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given brand "Goiko" with id "123" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/brands/edit/123"
        Then the response status should be 403