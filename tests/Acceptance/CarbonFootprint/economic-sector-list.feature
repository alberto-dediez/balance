Feature:
    Ability to list economic sectors in web

    Scenario: As admin I can access a list of all economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Item 1" with id "1111" exists
        Given economic sector "Item 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given economic sector "Item 1" with id "1111" exists
        Given economic sector "Item 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/economic-sectors"
        Then the response status should be 403

    Scenario: As admin I can filter economic sectors by classification code
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given economic sector "Fabricación de productos metálicos" with id "1111" and classification code "25" exists
        Given economic sector "Programación informática" with id "2222" and classification code "62.01" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/economic-sectors"
        Then I see a text "Código CNAE/NACE"
        When I filter by "classificationCode" with "25"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I see a text "Fabricación de productos metálicos"
        Then I do not see a text "Programación informática"
