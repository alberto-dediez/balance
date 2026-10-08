Feature:
    I want to search every entity I have access to from the top bar

    Scenario: As an admin I see the search button and the modal
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text 'data-bs-target="#global-search"'
        Then I see a text 'data-controller="global-search"'

    Scenario: An admin finds accounts and users grouped by category
        Given account "Acme" with id "a1" exists
        Given user "acme-owner@dediez.software" with role "ROLE_OWNER" of account "a1" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/search?term=acme" as an ajax request
        Then the response status should be 200
        Then I see a text 'data-search-category="accounts"'
        Then I see a text 'data-search-category="users"'
        Then I see a text "/es/admin/accounts/edit/a1"
        Then I see a text "/es/admin/users/edit/"
        Then I see a text "acme-owner@dediez.software"
        Then I see a text "Cuentas" before a text "Usuarios"

    Scenario: An owner finds only the users of its account
        Given account "Acme" with id "a1" exists
        Given account "Acme Rival" with id "a2" exists
        Given user "acme-owner@dediez.software" with role "ROLE_OWNER" with id "11111111-1111-1111-1111-111111111111" of account "a1" exists
        Given user "acme-rival@dediez.software" with role "ROLE_USER" with id "22222222-2222-2222-2222-222222222222" of account "a2" exists
        When I am logged as "acme-owner@dediez.software"
        When I go to "/es/users"
        Then I see a text 'data-bs-target="#global-search"'
        When I go to "/es/search?term=acme" as an ajax request
        Then the response status should be 200
        Then I see a text "/es/users/edit/11111111-1111-1111-1111-111111111111"
        Then I do not see a text "22222222-2222-2222-2222-222222222222"
        Then I do not see a text "acme-rival@dediez.software"
        Then I do not see a text 'data-search-category="accounts"'

    Scenario: A user does not see the search and the search is forbidden
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I do not see a text "global-search"
        When I go to "/es/search?term=acme" as an ajax request
        Then the response status should be 403

    Scenario: A short term does not search
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/search?term=a" as an ajax request
        Then the response status should be 200
        Then I see a text "Escribe al menos 2 caracteres"
        When I go to "/es/search?term=zzzz" as an ajax request
        Then the response status should be 200
        Then I see a text "Sin resultados"

    Scenario: At most five results per category
        Given account "Acme 1" with id "a1" exists
        Given account "Acme 2" with id "a2" exists
        Given account "Acme 3" with id "a3" exists
        Given account "Acme 4" with id "a4" exists
        Given account "Acme 5" with id "a5" exists
        Given account "Acme 6" with id "a6" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/search?term=acme" as an ajax request
        Then the response status should be 200
        Then I see 5 times the text "/es/admin/accounts/edit/"

    Scenario: An admin impersonating an account searches as its owner
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "Acme" with id "a1" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        Given user "acme-owner@dediez.software" with role "ROLE_OWNER" of account "a1" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "Acme"
        When I go to "/es/search?term=acme" as an ajax request
        Then the response status should be 200
        Then I see a text "/es/users/edit/"
        Then I see a text "acme-owner@dediez.software"
        Then I do not see a text "/es/admin/users/edit/"
        Then I do not see a text 'data-search-category="accounts"'

