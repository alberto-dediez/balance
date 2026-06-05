Feature:
    Any user can access and update his profile

    Scenario: Profile is reacheable for logged user
        Given user "admin@dediez.software" with role "ROLE_USER" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I see a form

    Scenario: As a logged user I can update my preferred locale
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:en" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Tu perfil se ha actualizado correctamente"
