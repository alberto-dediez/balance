Feature:
    Any user can access and update his profile

    Scenario: Profile is reacheable for logged user
        Given user "admin@dediez.software" with role "ROLE_USER" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I see a form

    Scenario: As a logged user I can pick a profile picture from the library
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given media "avatar.png" with id "1111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:es|picture:1111" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Tu perfil se ha actualizado correctamente"
        Then I see a text "/es/library/media/1111/show"

    Scenario: A picture picked for the profile becomes visible to the whole platform
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given media "avatar.png" with id "1111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:es|picture:1111" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then the file "avatar.png" has the visibility "System"
        Then the file "avatar.png" is not shared in the library

    Scenario: As a logged user I can remove my profile picture
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given media "avatar.png" with id "1111" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:es|picture:1111" and button "Guardar"
        When I follow redirect
        When I go to "/es/profile"
        Then I see a text "/es/library/media/1111/show"
        When I submit a form with "preferredLocale:es|picture:" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I do not see a text "/es/library/media/1111/show"

    Scenario: As a logged user I can update my preferred locale
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        When I submit a form with "preferredLocale:en" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Tu perfil se ha actualizado correctamente"
