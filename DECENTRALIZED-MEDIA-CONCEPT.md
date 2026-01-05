# Decentralized Media Concept - Planning Document

**Status**: Idea/Research Phase  
**Created**: January 5, 2026  
**Last Updated**: January 5, 2026

---

## 🎯 Core Concept

Create a decentralized family media sharing system where:
- Media files remain on users' **personal devices** (phones, laptops, PCs)
- Family Media Manager (WordPress) acts as a **central index/catalog**
- Media is **streamed directly** from owner's device to viewer
- All communication is **encrypted and secure**
- Uses **IPv6** addressing where possible

### Key Privacy Benefits
- Users maintain full control of their media
- Files never uploaded to central server
- Owner can revoke access at any time
- No storage limits from hosting provider

---

## 📋 Development Approach

### Priority Order
1. **C) Build simple proof-of-concept** - Test core functionality
2. **B) Research existing solutions** - Learn from what's already out there

---

## 🔬 Phase 1: Proof of Concept

**Goal**: Validate that the basic concept works before investing in full development.

### Proof of Concept Requirements
- [ ] Build basic desktop app that:
  - Registers with WordPress site (sends unique device ID)
  - Reports its current IP address
  - Can serve a single test video file
  - Maintains connection/heartbeat with WordPress

- [ ] Enhance WordPress plugin to:
  - Accept device registration
  - Store device info in database
  - Display list of registered devices
  - Route playback request to correct device

- [ ] Test streaming:
  - Can WordPress request video from device?
  - Can browser play video streamed from device?
  - Does it work on local network?
  - What about from outside network?

### Expected Timeline
2-3 weeks for basic proof of concept

### Technology Stack (Initial Ideas)
- **Desktop App**: Electron (JavaScript/Node.js) or Python with Flask
- **WordPress**: PHP with custom REST API endpoints
- **Communication**: WebSockets for persistent connection
- **Streaming**: Basic HTTP streaming initially

---

## 🔍 Phase 2: Research Existing Solutions

**Goal**: Learn from established projects to avoid reinventing the wheel and understand solved problems.

### Solutions to Research

#### 1. **Plex Media Server**
- How does it handle remote access?
- What's their approach to NAT traversal?
- How do they manage device discovery?
- What can we learn from their architecture?

#### 2. **Jellyfin** (Open Source)
- Similar to Plex but open source
- Can we examine their codebase?
- How do they handle security?
- What networking approach do they use?

#### 3. **Syncthing**
- Peer-to-peer file synchronization
- Excellent NAT traversal
- How do they discover devices?
- Could we adapt their relay server approach?

#### 4. **WebRTC Solutions**
- How does WebRTC handle NAT/firewall issues?
- STUN/TURN server requirements
- Peer-to-peer media streaming examples
- Security considerations

#### 5. **Tailscale/ZeroTier**
- Mesh VPN solutions
- Could simplify networking significantly
- Would users accept installing VPN software?
- What's the learning curve?

### Research Checklist
- [ ] Install and test Plex/Jellyfin locally
- [ ] Read documentation on WebRTC NAT traversal
- [ ] Explore Syncthing's relay protocol
- [ ] Investigate Tailscale for private networking
- [ ] Look at existing WordPress plugins for device management
- [ ] Research HTTPS certificate handling for local devices

---

## 🚧 Known Challenges

### 1. **NAT Traversal**
**Problem**: Home devices behind router, no public IP  
**Possible Solutions**:
- WebRTC with STUN/TURN servers
- Relay server (WordPress as middleman)
- User-configured port forwarding
- VPN mesh network (Tailscale, ZeroTier)
- UPnP automatic port mapping

**Research Needed**: Which solution is most user-friendly?

### 2. **Dynamic IP Addresses**
**Problem**: Home IP changes, WordPress loses track of device  
**Possible Solutions**:
- Persistent WebSocket connection
- Regular heartbeat updates (device pings WordPress every 2-5 minutes)
- Dynamic DNS service integration
- IPv6 unique addresses

**Research Needed**: Most reliable approach for consumer networks?

### 3. **Device Availability**
**Problem**: Device must be online for media to be accessible  
**Considerations**:
- Clear UI showing online/offline status
- Notification when offline media is requested
- Option for "always-on" primary device (home server/NAS)
- Mobile devices only share when app is active

**Design Decision**: How to communicate this to users?

### 4. **Security & Encryption**
**Problem**: Opening devices to internet is risky  
**Requirements**:
- End-to-end encryption for media streams
- Token-based authentication
- Per-session encryption keys
- Certificate validation
- Rate limiting to prevent abuse

**Research Needed**: Best practices for home device security?

### 5. **Bandwidth & Performance**
**Problem**: Home upload speeds limit streaming quality  
**Considerations**:
- Typical home upload: 5-20 Mbps
- HD video needs: 5-10 Mbps
- Multiple viewers could overwhelm connection
- Mobile devices on cellular data

**Design Decision**: Adaptive quality? Transcoding? Local caching?

### 6. **Cross-Platform Support**
**Problem**: Need apps for Windows, Mac, Linux, iOS, Android  
**Possible Solutions**:
- Electron for desktop (cross-platform)
- React Native or Flutter for mobile
- Or web-based PWA that runs in browser?

**Research Needed**: Which framework best suits our needs?

---

## 🏗️ Architecture Ideas (Evolving)

### Option A: Direct Peer-to-Peer
```
Viewer's Browser <--WebRTC--> Owner's Device App
         ↑                           ↑
         |------ WordPress ---------|
              (signaling only)
```
**Pros**: True peer-to-peer, no bandwidth cost to server  
**Cons**: Complex NAT traversal, requires WebRTC expertise

### Option B: WordPress as Relay
```
Viewer's Browser <--HTTPS--> WordPress <--HTTPS--> Owner's Device App
```
**Pros**: Simpler to implement, works through firewalls  
**Cons**: All bandwidth goes through server, scaling issues

### Option C: Hybrid Approach
```
- Try direct connection first (WebRTC)
- Fall back to relay if direct fails
- WordPress facilitates connection setup
```
**Pros**: Best of both worlds  
**Cons**: Most complex to implement

**Decision**: TBD after research and proof of concept

---

## 📱 App Features (Wishlist)

### Device App Features
- [ ] Auto-register with WordPress site
- [ ] Auto-detect local media files
- [ ] Upload metadata/thumbnails to WordPress
- [ ] Report online/offline status
- [ ] Handle streaming requests
- [ ] Encrypt outgoing streams
- [ ] Bandwidth monitoring
- [ ] Sleep/wake handling
- [ ] Background operation
- [ ] Low battery mode (mobile)

### WordPress Plugin Features
- [ ] Device management dashboard
- [ ] Device registration API
- [ ] Media catalog with "hosted by" info
- [ ] Online/offline device status
- [ ] Playback routing logic
- [ ] User permissions (who can access what)
- [ ] Encryption key management
- [ ] Connection statistics
- [ ] Troubleshooting tools
- [ ] Setup wizard for new devices

### User Features
- [ ] See which device hosts each video
- [ ] Filter by "available now" vs "offline"
- [ ] Request notification when media comes online
- [ ] Share specific videos with specific family members
- [ ] Revoke sharing at any time
- [ ] View bandwidth usage
- [ ] Quality selection (if transcoding added)

---

## 🎯 Success Criteria

How do we know if this idea is viable?

### Proof of Concept Success
- ✅ Device can register with WordPress
- ✅ WordPress can request file from device
- ✅ Video plays in browser
- ✅ Works on local network
- ✅ Basic encryption in place

### MVP Success (Minimum Viable Product)
- ✅ Works across internet (not just local network)
- ✅ Multiple devices supported
- ✅ Handles device going offline gracefully
- ✅ Secure authentication
- ✅ User-friendly setup process

### Production Ready
- ✅ Mobile apps released
- ✅ Handles NAT/firewall automatically
- ✅ Comprehensive security audit passed
- ✅ Performance tested with 10+ devices
- ✅ Documentation complete
- ✅ User testing shows 90%+ can set up without help

---

## 📅 Rough Timeline Estimate

| Phase | Duration | Description |
|-------|----------|-------------|
| Research | 1-2 weeks | Study existing solutions, document findings |
| Proof of Concept | 2-3 weeks | Basic desktop app + WordPress integration |
| Evaluation | 1 week | Decide if concept is viable |
| MVP Development | 2-3 months | Full desktop app, enhanced plugin |
| Mobile Apps | 2-3 months | iOS and Android versions |
| Testing & Refinement | 1-2 months | Security audit, performance testing |
| Documentation | 2-3 weeks | User guides, developer docs |
| **TOTAL** | **6-9 months** | For production-ready system |

*Note: Timeline assumes part-time development*

---

## 💡 Alternative: Simpler Hybrid Approach

**Concept**: Instead of custom apps, integrate with existing cloud storage.

### How It Would Work
- Users link their Google Drive, Dropbox, OneDrive, etc.
- WordPress stores links to cloud files
- When viewing, WordPress fetches from cloud storage
- Still private (files in user's cloud account)
- No custom app needed
- Leverages existing infrastructure

### Pros
- Much faster to implement (2-4 weeks vs 6+ months)
- Users already familiar with cloud storage
- No networking challenges
- No "device must be online" issues
- Mobile access built-in

### Cons
- Requires cloud storage account
- Subject to cloud provider's terms/limits
- Less "true decentralization"
- Monthly costs for storage (though free tiers exist)

**Decision**: Consider this as fallback if full decentralized approach proves too complex

---

## 📝 Research Notes

### [Date] - Research Topic
*Add research findings here as you discover them*

Example:
```
### 2026-01-05 - WebRTC NAT Traversal
- Found excellent guide: [URL]
- Key learning: STUN servers work for 80% of cases
- TURN relay needed for remaining 20%
- Free STUN servers available (Google, etc.)
- TURN servers need to be self-hosted or paid
```

---

## ✅ Action Items

### Immediate (This Week)
- [ ] Review this planning document
- [ ] Add any additional ideas/concerns
- [ ] Decide on technology for proof of concept (Electron? Python?)
- [ ] Set up development environment

### Short Term (2-4 Weeks)
- [ ] Research Phase: Study Plex, Jellyfin, Syncthing
- [ ] Document research findings
- [ ] Build proof of concept
- [ ] Test on local network

### Medium Term (1-3 Months)
- [ ] Evaluate proof of concept results
- [ ] Decide: full implementation or simpler alternative?
- [ ] Create detailed technical specification
- [ ] Begin MVP development

---

## 🤔 Open Questions

Questions to answer during research and development:

1. **Networking**: What's the most reliable way to handle NAT traversal for non-technical users?

2. **Security**: What level of encryption is necessary? What's overkill?

3. **User Experience**: How do we make setup simple enough for grandma?

4. **Mobile**: Should mobile devices only share while app is open, or should they support background sharing?

5. **Fallback**: If device is offline, should we offer to cache on WordPress server?

6. **Licensing**: What open source license should we use? MIT? GPL?

7. **Platform Priority**: Which platform to build first? Windows? Mac? Linux? Mobile?

8. **Testing**: How do we test across different network configurations?

9. **Bandwidth**: Should we implement adaptive streaming or transcoding?

10. **Discovery**: How do devices find the WordPress site initially?

---

## 📚 Resources & Links

*Add useful links here as you find them*

### Documentation
- [WebRTC Basics](https://webrtc.org/getting-started/overview)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [Electron Documentation](https://www.electronjs.org/docs/latest/)

### Similar Projects
- [Plex](https://www.plex.tv/)
- [Jellyfin](https://jellyfin.org/)
- [Syncthing](https://syncthing.net/)

### Tutorials
- *Add helpful tutorials as discovered*

---

## 💬 Evolution Notes

*Document how the idea evolves over time*

### 2026-01-05 - Initial Concept
- Conceived idea of decentralized media sharing
- Focus on privacy and control
- IPv6 mentioned as potential addressing solution
- Identified need for mobile and desktop apps

---

## 🎉 Milestones

Track major achievements:

- [ ] Planning document created
- [ ] Research phase completed
- [ ] Proof of concept working
- [ ] First successful remote streaming
- [ ] MVP completed
- [ ] Mobile apps launched
- [ ] v1.0 released

---

**Remember**: This is a living document. Update it as ideas evolve, research uncovers new information, and testing reveals what works (and what doesn't)!
