Feature:
    I want an account administrator to be able to personalise the public look of their account

    Scenario: An owner can set the palette of their own account
        Given account "MyCompany" with id "666" exists
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to "/es/branding"
        Then the response status should be 200
        Then I see a form
        When I submit a form with "primary:#8A2432|webFontFamily:Poppins|webFontWeights:400;700" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Personalización actualizada"
        Then account "666" branding primary colour should be "#8A2432"

    Scenario: An invalid colour is rejected
        Given account "MyCompany" with id "666" exists
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to "/es/branding"
        When I submit a form with "primary:not-a-colour" and button "Guardar"
        Then I see a text "Introduce un color hexadecimal de 6 dígitos"
        Then account "666" should have no branding primary colour

    Scenario: An invalid font family is rejected
        Given account "MyCompany" with id "666" exists
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to "/es/branding"
        When I submit a form with 'webFontFamily:Inter";}' and button "Guardar"
        Then I see a text "no es un nombre de familia válido"
        Then account "666" should have no branding primary colour

    Scenario: An admin can preview the branding of any account
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        When I am logged as "admin"
        When I go to "/es/admin/accounts/666/branding"
        Then the response status should be 200
        When I submit a form with "primary:#8A2432" and button "Vista previa"
        Then the response status should be 200

    Scenario: An owner can preview the branding of their own account
        Given account "MyCompany" with id "666" exists
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to "/es/branding"
        When I submit a form with "primary:#8A2432" and button "Vista previa"
        Then the response status should be 200

    Scenario: The preview shows the saved branding when it is opened without submitting the form
        Given account "MyCompany" with id "666" exists
        Given account "666" has branding with primary colour "#8A2432"
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to the branding preview of account "666"
        Then the response status should be 200
        Then I see a text '--brand-font-family:"Poppins", sans-serif'
        Then I see a text "--brand-primary:#8A2432"

    Scenario: The preview does not show the public cookie banner nor the public footer
        Given account "MyCompany" with id "666" exists
        Given account "666" has branding with primary colour "#8A2432"
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to the branding preview of account "666"
        Then the response status should be 200
        Then I do not see a text "cookie-consent"
        Then I do not see a text "public-footer"

    Scenario: An owner cannot reach the branding of another account
        Given account "MyCompany" with id "666" exists
        Given account "Other" with id "777" exists
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to "/es/admin/accounts/777/branding"
        Then the response status should be 403

    Scenario: An owner does not see the theme selector
        Given account "MyCompany" with id "666" exists
        Given user "owner" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner"
        When I go to "/es/branding"
        Then I do not see a text "Tema visual"

    Scenario: An admin sees the theme selector
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        When I am logged as "admin"
        When I go to "/es/admin/accounts/666/branding"
        Then the response status should be 200
        Then I see a text "Tema visual"
        Then I see a text "Classic"
