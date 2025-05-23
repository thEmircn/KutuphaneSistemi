// import node module libraries
import { Container } from 'react-bootstrap';

// import widget as custom components
import { PageHeading } from 'widgets'

// import sub components
import { Notifications, SilAccount, GeneralSetting, EmailSetting, Preferences } from 'sub-components'

const Ayarlar = () => {
  return (
    <Container fluid className="p-6">

      {/* Page Heading */}
      <PageHeading heading="General" />

      {/* General Ayarlar */}
      <GeneralSetting />

      {/* Email Ayarlar */}
      <EmailSetting />

      {/* Ayarlar for Preferences */}
      <Preferences />

      {/* Ayarlar for Notifications */}
      <Notifications />

      {/* Sil Your Account */}
      <SilAccount />

    </Container>
  )
}

export default Ayarlar